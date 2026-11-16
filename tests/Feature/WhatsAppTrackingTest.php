<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Product;
use App\Models\Setting;
use App\Models\WhatsAppClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Rate limiter state must not leak between tests (cache store flush).
        app(\Illuminate\Cache\CacheManager::class)->getStore()->flush();
    }

    public function test_product_click_redirects_to_wa_me_and_records_click(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        $product = Product::factory()->published()->create(['name' => 'Kamera IP 4MP', 'slug' => 'kamera-ip-4mp']);

        Queue::fake();

        $response = $this->withServerVariables(['HTTP_REFERER' => 'https://example.com/ref'])
            ->get('/track/whatsapp?context=product&slug=kamera-ip-4mp');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $location);
        $this->assertStringContainsString(rawurlencode('Kamera IP 4MP'), $location);
        $this->assertStringNotContainsString('https://example.com', $location);

        // Click recorded with all §7.3 fields.
        $click = WhatsAppClick::first();
        $this->assertNotNull($click);
        $this->assertSame($product->id, $click->context_id);
        $this->assertSame('product', $click->page_type);
        $this->assertSame('https://example.com/ref', $click->referrer);
        $this->assertNotEmpty($click->event_id);
        $this->assertNotEmpty($click->page_url);
        $this->assertSame('Kamera IP 4MP', $click->page_title);
        $this->assertContains($click->device_category, ['desktop', 'mobile', 'tablet']);

        // Notification job dispatched but never awaited.
        Queue::assertPushed(SendWhatsAppNotificationJob::class);
    }

    public function test_redirect_never_uses_arbitrary_destination(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        // Malicious-looking inputs must not change the wa.me destination.
        $response = $this->get('/track/whatsapp?context=product&slug=cam&utm_source=https://evil.com');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $location);
        $this->assertStringNotContainsString('evil.com', $location);
    }

    public function test_invalid_context_returns_404(): void
    {
        Setting::set('whatsapp_number', '6281234567890');

        $this->get('/track/whatsapp?context=hacker')->assertNotFound();
        $this->get('/track/whatsapp')->assertNotFound();
        $this->get('/track/whatsapp?context=')->assertNotFound();

        $this->assertDatabaseCount('whatsapp_clicks', 0);
    }

    public function test_missing_or_unpublished_slug_returns_404(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        Product::factory()->create(['slug' => 'draft-cam', 'name' => 'Draft', 'published' => false]);

        $this->get('/track/whatsapp?context=product&slug=nope')->assertNotFound();
        $this->get('/track/whatsapp?context=product&slug=draft-cam')->assertNotFound();

        $this->assertDatabaseCount('whatsapp_clicks', 0);
    }

    public function test_home_context_works_without_slug(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        Queue::fake();

        $response = $this->get('/track/whatsapp?context=home');

        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $response->headers->get('Location'));

        $click = WhatsAppClick::first();
        $this->assertSame('home', $click->page_type);
        $this->assertNull($click->context_id);
    }

    public function test_unconfigured_phone_returns_503(): void
    {
        Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        $response = $this->get('/track/whatsapp?context=product&slug=cam');

        $response->assertStatus(503);
        $response->assertSee('WhatsApp belum dikonfigurasikan');

        $this->assertDatabaseCount('whatsapp_clicks', 0);
    }

    public function test_eleventh_request_in_minute_returns_429(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        $product = Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        $cookies = [
            \App\Services\WhatsAppTrackingService::VISITOR_COOKIE => str_repeat('a', 32),
        ];

        $last = null;
        for ($i = 0; $i < 10; $i++) {
            $last = $this->call(
                'GET',
                '/track/whatsapp?context=product&slug=cam',
                $cookies
            );
        }

        $this->assertSame(302, $last->getStatusCode());
        $this->assertSame(10, WhatsAppClick::count());

        $eleventh = $this->call(
            'GET',
            '/track/whatsapp?context=product&slug=cam',
            $cookies
        );

        $this->assertSame(429, $eleventh->getStatusCode());
        $this->assertSame(10, WhatsAppClick::count(), 'No record on rate-limited request.');
    }

    public function test_visitor_cookie_set_on_first_response_reused_after(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        $first = $this->get('/track/whatsapp?context=product&slug=cam');

        $cookies = $first->headers->getCookies();
        $cookieSet = collect($cookies)->first(fn ($c) => $c->getName() === 'wpc_visit');

        $this->assertNotNull($cookieSet, 'wpc_visit cookie must be set on first response');
        $this->assertSame(32, strlen($cookieSet->getValue()));
        $this->assertTrue($cookieSet->isHttpOnly());
        $this->assertSame(
            \Symfony\Component\HttpFoundation\Cookie::SAMESITE_LAX,
            $cookieSet->getSameSite()
        );

        // Second request with the same cookie → no new cookie, token reused.
        // withUnencryptedCookie() mirrors the real client: the browser stores
        // wpc_visit as a plaintext hex value and sends it back as-is.
        $token = $cookieSet->getValue();
        $second = $this->withUnencryptedCookie('wpc_visit', $token)
            ->get('/track/whatsapp?context=product&slug=cam');

        // applyVisitorCookie() only attaches a cookie when one was minted, so
        // a request that already carried wpc_visit must not produce a fresh
        // wpc_visit Set-Cookie header on the second response.
        $newCookie = collect($second->headers->getCookies())
            ->first(fn ($c) => $c->getName() === 'wpc_visit');

        $this->assertNull($newCookie, 'Cookie must not be re-set when already present.');

        // The first request arrived without a cookie, so its click row has
        // no token (the cookie is minted on that response only). The second
        // request carries the token, so its click row stores it.
        //
        // Ordering is pinned by `id` as well as `created_at`. Both requests
        // land inside the same wall-clock second, so `created_at` alone is a
        // tie and `latest()` (bare `ORDER BY created_at DESC`) then returns
        // whichever row the plan happens to emit first — the stored
        // timestamps really are identical, and SQLite's plan for this shape
        // is a `SCAN ... USING INDEX whatsapp_clicks_created_at_index`.
        // Without the tiebreaker this assertion flips between runs.
        $clicks = WhatsAppClick::orderByDesc('created_at')->orderByDesc('id')->take(2)->get();
        $this->assertNull($clicks[1]->visitor_token, 'First click has no token (cookie not yet present).');
        $this->assertSame($token, $clicks[0]->visitor_token, 'Second click reuses the token.');
    }

    public function test_utm_parameters_recorded(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        $this->get(
            '/track/whatsapp?context=product&slug=cam&utm_source=google&utm_medium=cpc&utm_campaign=launch'
        )->assertRedirect();

        $click = WhatsAppClick::first();
        $this->assertSame('google', $click->utm_source);
        $this->assertSame('cpc', $click->utm_medium);
        $this->assertSame('launch', $click->utm_campaign);
    }
}
