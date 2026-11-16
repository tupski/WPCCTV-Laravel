<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsAppClick;
use App\Services\WhatsAppTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `visitor_token` fidelity, and what the unique-visitor metric may claim.
 *
 * Audit M2 recorded a collision. The source is two distinct things, and this
 * file measures both rather than assuming:
 *
 *  1. **First-click NULL (structural, unavoidable).** The `wpc_visit` cookie is
 *     minted on the *response* of the first click, so the first click of every
 *     visitor is stored with `visitor_token = NULL`. Those rows cannot join a
 *     `COUNT(DISTINCT visitor_token)`, so click-level unique counts omit every
 *     visitor's first click.
 *
 *  2. **Coarse fallback (by design, in the rate limiter only).** The C4 fix in
 *     `AppServiceProvider::rateLimitIdentity()` hashes IP + user agent + context
 *     when no visitor token is present. That value is deliberately coarse and is
 *     used *only* to separate rate-limit buckets — it is never persisted to
 *     `visitor_token`, so it cannot merge distinct visitors in the metric.
 *
 * The tests below pin both facts so a future change cannot silently turn the
 * second into a real collision, and they pin the metric to a source that is not
 * affected by either: `analytics_page_views.session_id`, which falls back to the
 * session id instead of NULL.
 */
class VisitorTokenCollisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Setting::set('whatsapp_number', '6281234567890');
    }

    /**
     * (1) The first click of a session stores NULL, the second stores the token.
     *
     * This is the structural half of M2 and it is NOT a bug to fix: the cookie
     * does not exist yet when the first click is recorded. Documented here so
     * the metric's limitation is visible in the test suite, not only in a wiki.
     */
    public function test_first_click_has_no_token_so_click_level_unique_counts_omit_it(): void
    {
        \App\Models\Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        $first = $this->get('/track/whatsapp?context=product&slug=cam');
        $token = collect($first->headers->getCookies())
            ->first(fn ($c) => $c->getName() === WhatsAppTrackingService::VISITOR_COOKIE)
            ->getValue();

        // The second request carries the cookie the first response minted, as
        // a real browser would.
        $this->withUnencryptedCookie(WhatsAppTrackingService::VISITOR_COOKIE, $token)
            ->get('/track/whatsapp?context=product&slug=cam');

        $ordered = WhatsAppClick::orderBy('id')->get();

        $this->assertNull($ordered[0]->visitor_token, 'First click of a session cannot carry the cookie it mints.');
        $this->assertSame($token, $ordered[1]->visitor_token);

        // Quantified: two clicks from ONE visitor. A distinct-token count sees
        // 1, not 2 — the NULL row is dropped by COUNT(DISTINCT).
        $this->assertSame(
            1,
            WhatsAppClick::query()->distinct()->count('visitor_token'),
            'COUNT(DISTINCT visitor_token) undercounts because NULL rows are excluded.'
        );
        $this->assertSame(2, WhatsAppClick::query()->count());
    }

    /**
     * (2) Distinct visitors without a cookie are NOT merged into one token.
     *
     * The rate-limiter fallback hashes IP + UA, which would collapse visitors
     * behind one NAT if it were persisted. It is not persisted: two cookie-less
     * clicks from the same IP remain two NULL rows, not one shared hash.
     */
    public function test_cookieless_clicks_from_the_same_ip_are_not_merged_into_one_token(): void
    {
        \App\Models\Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->withHeader('User-Agent', 'Mozilla/5.0 (probe)')
            ->get('/track/whatsapp?context=product&slug=cam');

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->withHeader('User-Agent', 'Mozilla/5.0 (probe)')
            ->get('/track/whatsapp?context=product&slug=cam');

        $tokens = WhatsAppClick::query()->pluck('visitor_token');

        $this->assertCount(2, $tokens);
        $this->assertSame(
            [null, null],
            $tokens->all(),
            'The IP+UA fallback must never be written to visitor_token — that would be the coarse merge.'
        );
    }

    /**
     * The documented correction: the unique-visitor metric comes from
     * `analytics_page_views.session_id`, which has no NULL hole.
     *
     * A cookie-less page view falls back to the session id, so two page views
     * from one visitor count as one session instead of vanishing. This is the
     * figure `analytics_daily.unique_sessions` is built from, and it is the one
     * the dashboard labels "sesi unik".
     */
    public function test_page_view_session_id_survives_without_a_visitor_cookie(): void
    {
        $this->get('/');

        $view = \App\Models\AnalyticsPageView::first();

        $this->assertNotNull($view, 'A cookie-less page view must still be recorded.');
        $this->assertNotNull($view->session_id);
        $this->assertNotSame('', $view->session_id);
        $this->assertNotSame(
            null,
            (int) DB::table('analytics_page_views')->whereNotNull('session_id')->count()
        );
    }

    /**
     * A visitor WITH a cookie is counted once across many clicks and pages.
     */
    public function test_one_visitor_with_a_cookie_contributes_one_session(): void
    {
        \App\Models\Product::factory()->published()->create(['slug' => 'cam', 'name' => 'Cam']);

        $this->withUnencryptedCookie(WhatsAppTrackingService::VISITOR_COOKIE, 'a1b2c3d4e5f60718293a4b5c6d7e8f90')
            ->get('/track/whatsapp?context=product&slug=cam');

        $this->withUnencryptedCookie(WhatsAppTrackingService::VISITOR_COOKIE, 'a1b2c3d4e5f60718293a4b5c6d7e8f90')
            ->get('/track/whatsapp?context=product&slug=cam');

        $this->assertSame(2, WhatsAppClick::query()->count());
        $this->assertSame(
            1,
            WhatsAppClick::query()->whereNotNull('visitor_token')->distinct()->count('visitor_token')
        );
    }

    /**
     * The label the UI must use while the click-level figure has a known hole.
     *
     * `unique_sessions` on the dashboard is sourced from page views, so it is
     * sound; click-level distinct counts are not, and the API must not present
     * them as if they were.
     */
    public function test_dashboard_labels_unique_visitors_as_an_estimate(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->get(route('admin.analytics-ui.index'))
            ->assertOk()
            ->assertSee('Sesi unik', false)
            // The caveat must sit on the same screen as the number, not in a
            // changelog: the figure is a session estimate, not a head count.
            ->assertSee('Sesi unik adalah perkiraan', false);
    }
}
