<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\WhatsAppClick;
use App\Services\WhatsAppTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for PHASE_4_CLOSURE_AUDIT.md C4.
 *
 * Root cause confirmed at app/Providers/AppServiceProvider.php:36 — the
 * limiter keyed on `$request->cookie('wpc_visit')`, which is null on a
 * visitor's first click because the cookie is only set on the response. Every
 * cookie-less visitor therefore shared the bucket `whatsapp-:product`; the
 * audit measured 12 requests → 429 across distinct source IPs.
 *
 * These tests deliberately send no cookies at all — that is the condition the
 * old key could not distinguish.
 */
class WhatsAppRateLimiterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(\Illuminate\Cache\CacheManager::class)->getStore()->flush();
    }

    private function hit(string $ip, string $context = 'product'): \Illuminate\Testing\TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get("/track/whatsapp?context={$context}&slug=cam");
    }

    /**
     * Two distinct, cookie-less visitors must each get their own 10/min budget.
     */
    public function test_distinct_ips_each_get_their_own_budget(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        // IP A exhausts its budget: 10 allowed, 11th limited.
        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(302, $this->hit('203.0.113.10')->getStatusCode(), 'Request '.($i + 1).' from IP A should be allowed.');
        }

        $this->assertSame(429, $this->hit('203.0.113.10')->getStatusCode(), 'The 11th request from IP A must be limited.');

        // IP B, still cookie-less, must be unaffected by IP A's exhaustion.
        $first = $this->hit('203.0.113.20');
        $this->assertSame(
            302,
            $first->getStatusCode(),
            'A distinct cookie-less IP must not inherit another IP\'s exhausted bucket.'
        );

        // And IP B gets the full allowance of its own.
        for ($i = 0; $i < 9; $i++) {
            $this->assertSame(302, $this->hit('203.0.113.20')->getStatusCode());
        }

        $this->assertSame(429, $this->hit('203.0.113.20')->getStatusCode());
    }

    /**
     * The 11th request from one IP is still limited — the fix must not have
     * disabled throttling in the process.
     */
    public function test_eleventh_request_from_one_ip_is_still_limited(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        for ($i = 0; $i < 10; $i++) {
            $this->hit('198.51.100.7')->assertStatus(302);
        }

        $this->assertSame(10, WhatsAppClick::count(), 'Exactly ten clicks recorded before the limit.');

        $this->hit('198.51.100.7')->assertStatus(429);
        $this->assertSame(10, WhatsAppClick::count(), 'No row written for a rate-limited request.');
    }

    /**
     * Distinct contexts must remain independent budgets for the same requester.
     */
    public function test_distinct_contexts_do_not_share_a_budget(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        for ($i = 0; $i < 10; $i++) {
            $this->hit('198.51.100.30', 'product')->assertStatus(302);
        }

        $this->assertSame(429, $this->hit('198.51.100.30', 'product')->getStatusCode());

        // Same IP, same (still absent) cookie, different context → own budget.
        $this->assertSame(
            302,
            $this->hit('198.51.100.30', 'home')->getStatusCode(),
            'The home context must not be limited by the product context.'
        );
    }

    /**
     * A caller that does present the visitor token gets that identity, so the
     * token — not the IP — is what partitions the bucket.
     */
    public function test_presented_visitor_token_takes_precedence_without_sharing_globally(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        $tokenA = str_repeat('a', 32);
        $tokenB = str_repeat('b', 32);

        for ($i = 0; $i < 10; $i++) {
            $this->withUnencryptedCookie(WhatsAppTrackingService::VISITOR_COOKIE, $tokenA)
                ->withServerVariables(['REMOTE_ADDR' => '198.51.100.40'])
                ->get('/track/whatsapp?context=product&slug=cam')
                ->assertStatus(302);
        }

        $this->withUnencryptedCookie(WhatsAppTrackingService::VISITOR_COOKIE, $tokenA)
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.40'])
            ->get('/track/whatsapp?context=product&slug=cam')
            ->assertStatus(429);

        // Different token, same IP → different bucket.
        $this->withUnencryptedCookie(WhatsAppTrackingService::VISITOR_COOKIE, $tokenB)
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.40'])
            ->get('/track/whatsapp?context=product&slug=cam')
            ->assertStatus(302);
    }

    /**
     * A distinct user agent on the same IP is a distinct identity — the
     * fallback key is IP + UA + context, never IP alone.
     */
    public function test_fallback_key_distinguishes_user_agents_on_one_ip(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        for ($i = 0; $i < 10; $i++) {
            $this->withServerVariables([
                'REMOTE_ADDR' => '198.51.100.50',
                'HTTP_USER_AGENT' => 'FloodBot/1.0',
            ])->get('/track/whatsapp?context=product&slug=cam')->assertStatus(302);
        }

        $this->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.50',
            'HTTP_USER_AGENT' => 'FloodBot/1.0',
        ])->get('/track/whatsapp?context=product&slug=cam')->assertStatus(429);

        $this->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.50',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone)',
        ])->get('/track/whatsapp?context=product&slug=cam')->assertStatus(302);
    }
}
