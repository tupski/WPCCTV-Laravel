<?php

namespace Tests\Feature;

use App\Models\AnalyticsPageView;
use App\Models\Setting;
use App\Models\User;
use App\Services\WhatsAppTrackingService;
use App\Support\DeviceDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class AnalyticsRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_records_a_page_view(): void
    {
        $this->withServerVariables(['HTTP_REFERER' => 'https://www.google.com/search?q=cctv'])
            ->get('/?utm_source=google&utm_medium=organic&utm_campaign=launch')
            ->assertOk();

        $view = AnalyticsPageView::first();

        $this->assertNotNull($view);
        $this->assertNotEmpty($view->session_id);
        $this->assertLessThanOrEqual(100, mb_strlen($view->session_id));
        $this->assertSame('/', $view->page_url);
        $this->assertSame('home', $view->page_type);
        $this->assertSame('www.google.com', $view->ref_domain);
        $this->assertSame('google', $view->utm_source);
        $this->assertSame('organic', $view->utm_medium);
        $this->assertSame('launch', $view->utm_campaign);
        $this->assertSame(DeviceDetector::DESKTOP, $view->device_category);
    }

    public function test_visitor_cookie_token_is_preferred_over_session_id(): void
    {
        $request = Request::create('/', 'GET', [], [WhatsAppTrackingService::VISITOR_COOKIE => 'visitor-token-123']);
        $request->setLaravelSession(app('session.store'));

        $recorder = app(\App\Services\AnalyticsRecorderService::class);
        $recorder->recordPageView($request, 'home');

        $this->assertSame('visitor-token-123', AnalyticsPageView::first()->session_id);
    }

    public function test_session_id_is_used_when_visitor_cookie_absent(): void
    {
        $request = Request::create('/', 'GET');
        $session = app('session.store');
        $session->start();
        $request->setLaravelSession($session);

        app(\App\Services\AnalyticsRecorderService::class)->recordPageView($request, 'home');

        $this->assertSame($session->getId(), AnalyticsPageView::first()->session_id);
    }

    public function test_referrer_is_reduced_to_host_only(): void
    {
        $this->withServerVariables([
            'HTTP_REFERER' => 'https://shop.example.com/deep/path?secret=1',
        ])->get('/')->assertOk();

        $view = AnalyticsPageView::first();

        $this->assertSame('shop.example.com', $view->ref_domain);
    }

    public function test_mobile_user_agent_is_categorized(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148')
            ->get('/')
            ->assertOk();

        $this->assertSame(DeviceDetector::MOBILE, AnalyticsPageView::first()->device_category);
    }

    public function test_admin_pages_are_not_recorded(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();

        $this->assertSame(0, AnalyticsPageView::count());
    }

    public function test_whatsapp_tracking_endpoint_is_not_recorded(): void
    {
        Setting::set('whatsapp_number', '6281234567890');

        $this->get('/track/whatsapp?context=home')->assertRedirect();

        $this->assertSame(0, AnalyticsPageView::count());
    }

    public function test_login_page_is_not_recorded(): void
    {
        $this->get('/login')->assertOk();

        $this->assertSame(0, AnalyticsPageView::count());
    }

    public function test_dnt_header_is_honored(): void
    {
        $this->withHeader('DNT', '1')->get('/')->assertOk();

        $this->assertSame(0, AnalyticsPageView::count());
    }

    public function test_sec_gpc_header_is_honored(): void
    {
        $this->withHeader('Sec-GPC', '1')->get('/')->assertOk();

        $this->assertSame(0, AnalyticsPageView::count());
    }

    public function test_recorder_never_throws_and_page_still_renders(): void
    {
        Log::spy();

        $this->app->instance(DeviceDetector::class, new class extends DeviceDetector
        {
            public function detect(Request $request): string
            {
                throw new RuntimeException('device detection failure');
            }
        });

        $this->get('/')->assertOk();

        $this->assertSame(0, AnalyticsPageView::count());
        Log::shouldHaveReceived('warning')->atLeast()->once();
    }

    public function test_non_get_requests_are_not_recorded(): void
    {
        $this->post('/login', ['email' => 'a@b.c', 'password' => 'wrong']);

        $this->assertSame(0, AnalyticsPageView::count());
    }
}
