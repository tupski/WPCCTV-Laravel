<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\WhatsAppClick;
use App\Services\WhatsAppTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for PHASE_4_CLOSURE_AUDIT.md H2.
 *
 * Root cause confirmed at app/Services/WhatsAppTrackingService.php:158 — the
 * raw `Referer` header was written straight into `whatsapp_clicks.referrer`
 * (`$table->string('referrer')->nullable()`, varchar 255). Referrer URLs and
 * UTM parameters routinely exceed 255 characters; MySQL/MariaDB strict mode
 * rejects the insert with SQLSTATE 22001, aborting the click before the
 * redirect. SQLite does not enforce VARCHAR length, which is why the suite
 * never caught it.
 *
 * Decision: truncate at the recording boundary and do NOT widen the column.
 * A 255-char referrer is still a usable analytics dimension, while widening
 * would size the schema to unbounded, attacker-controlled request input.
 */
class ReferrerTruncationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(\Illuminate\Cache\CacheManager::class)->getStore()->flush();
        Setting::set('whatsapp_number', '6281234567890');
    }

    public function test_two_thousand_character_referrer_persists_without_error(): void
    {
        Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        $referrer = 'https://referrer.example.com/'.str_repeat('x', 2000);

        $response = $this->withServerVariables(['HTTP_REFERER' => $referrer])
            ->get('/track/whatsapp?context=product&slug=cam');

        // The click must complete: the audit's failure mode was a 500 here.
        $response->assertRedirect();
        $this->assertStringStartsWith(
            'https://wa.me/6281234567890?text=',
            $response->headers->get('Location')
        );

        $click = WhatsAppClick::firstOrFail();

        $this->assertNotNull($click->referrer);
        $this->assertLessThanOrEqual(
            WhatsAppTrackingService::DIMENSION_MAX_LENGTH,
            mb_strlen($click->referrer),
            'referrer must be truncated to the varchar(255) column width.'
        );
        $this->assertSame(255, mb_strlen($click->referrer));
        $this->assertStringStartsWith('https://referrer.example.com/', $click->referrer);
    }

    public function test_oversized_utm_parameters_are_truncated_too(): void
    {
        Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        $long = str_repeat('u', 900);

        $this->get('/track/whatsapp?context=product&slug=cam'
            .'&utm_source='.$long
            .'&utm_medium='.$long
            .'&utm_campaign='.$long)
            ->assertRedirect();

        $click = WhatsAppClick::firstOrFail();

        foreach (['utm_source', 'utm_medium', 'utm_campaign'] as $column) {
            $this->assertSame(
                255,
                mb_strlen($click->{$column}),
                "{$column} must be truncated to the column width."
            );
        }
    }

    /**
     * Short values must pass through untouched — truncation must not mangle
     * ordinary analytics data.
     */
    public function test_short_referrer_is_preserved_verbatim(): void
    {
        Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        $referrer = 'https://example.com/blog/tips-cctv?utm_source=google';

        $this->withServerVariables(['HTTP_REFERER' => $referrer])
            ->get('/track/whatsapp?context=product&slug=cam')
            ->assertRedirect();

        $this->assertSame($referrer, WhatsAppClick::firstOrFail()->referrer);
    }

    /**
     * Every string dimension the click writes must respect the column width,
     * so a future long field cannot reintroduce the same 22001.
     */
    public function test_all_string_dimensions_fit_the_column_width(): void
    {
        $product = Product::factory()->published()->create([
            'slug' => 'cam',
            'name' => str_repeat('N', 400),
        ]);

        $this->withServerVariables(['HTTP_REFERER' => 'https://r.example/'.str_repeat('y', 1200)])
            ->get('/track/whatsapp?context=product&slug=cam')
            ->assertRedirect();

        $click = WhatsAppClick::firstOrFail();
        $limit = WhatsAppTrackingService::DIMENSION_MAX_LENGTH;

        foreach (['page_url', 'page_title', 'referrer', 'visitor_token'] as $column) {
            $value = $click->{$column};
            if ($value !== null) {
                $this->assertLessThanOrEqual($limit, mb_strlen($value), "{$column} exceeds {$limit} chars.");
            }
        }

        $this->assertSame($product->id, $click->context_id);
    }
}
