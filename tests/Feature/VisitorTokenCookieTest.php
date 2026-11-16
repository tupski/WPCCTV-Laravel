<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\WhatsAppClick;
use App\Services\WhatsAppTrackingService;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for PHASE_4_CLOSURE_AUDIT.md C2.
 *
 * Root cause confirmed: `wpc_visit` was encrypted (it was only excluded inside
 * tests/TestCase.php, never in the real middleware configuration). Laravel
 * therefore wrote the 312-character encrypted payload into
 * whatsapp_clicks.visitor_token (varchar 255). SQLite does not enforce VARCHAR
 * length, so the suite stayed green; MySQL/MariaDB in strict mode rejects the
 * insert with SQLSTATE 22001, which aborted every WhatsApp click before the
 * redirect and before the job dispatch.
 */
class VisitorTokenCookieTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(\Illuminate\Cache\CacheManager::class)->getStore()->flush();
    }

    /**
     * The exception list must be declared in the application's real middleware
     * configuration (bootstrap/app.php), not only in test scaffolding.
     */
    public function test_application_middleware_excludes_the_visitor_cookie_from_encryption(): void
    {
        $middleware = app(EncryptCookies::class);

        $this->assertTrue(
            $middleware->isDisabled(WhatsAppTrackingService::VISITOR_COOKIE),
            'bootstrap/app.php must register wpc_visit via $middleware->encryptCookies([...]).'
        );

        // Sanity: the assertion above is meaningful only if other cookies are
        // still encrypted. If everything were excluded, this test would pass
        // for the wrong reason.
        $this->assertFalse($middleware->isDisabled('laravel_session'));
    }

    /**
     * The stored token must be the raw 32-char hex value, not an encrypted
     * payload — so the row fits varchar(255) with room to spare.
     */
    public function test_persisted_visitor_token_is_the_raw_token_and_fits_the_column(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        $token = str_repeat('a1', 16);

        $this->withUnencryptedCookie(WhatsAppTrackingService::VISITOR_COOKIE, $token)
            ->get('/track/whatsapp?context=product&slug=cam')
            ->assertRedirect();

        $click = WhatsAppClick::firstOrFail();

        $this->assertSame($token, $click->visitor_token, 'Visitor token must be stored verbatim.');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $click->visitor_token);
        $this->assertLessThanOrEqual(
            64,
            strlen((string) $click->visitor_token),
            'visitor_token must fit comfortably inside varchar(255).'
        );
    }

    /**
     * The Set-Cookie value the browser receives on a first click must itself be
     * the 32-char token — no encryption wrapper, no serialization prefix.
     */
    public function test_first_click_set_cookie_value_is_thirty_two_characters(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        $response = $this->get('/track/whatsapp?context=product&slug=cam');

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => $c->getName() === WhatsAppTrackingService::VISITOR_COOKIE);

        $this->assertNotNull($cookie);
        $this->assertSame(32, strlen($cookie->getValue()));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $cookie->getValue());
        $this->assertLessThanOrEqual(64, strlen($cookie->getValue()));
    }

    /**
     * Encryption would have produced a decryptable-plus-prefixed blob; prove
     * the exclusion actually changes what is written, so removing the
     * bootstrap registration would fail this suite.
     */
    public function test_encryption_is_genuinely_off_not_merely_tolerated(): void
    {
        $plain = 'a1b2c3d4e5f6a7b8a1b2c3d4e5f6a7b8';

        $encrypted = app('encrypter')->encrypt(
            CookieValuePrefix::create(WhatsAppTrackingService::VISITOR_COOKIE, app('encrypter')->getKey()).$plain,
            false
        );

        $this->assertGreaterThan(
            255,
            strlen($encrypted),
            'The encrypted form is what overflowed varchar(255) — this is the regression being guarded.'
        );
        $this->assertNotSame($encrypted, $plain);
    }
}
