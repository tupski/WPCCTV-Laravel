<?php

namespace Tests\Feature;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsPageView;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsAppClick;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function analyst(): User
    {
        return User::factory()->create(['role' => User::ROLE_ANALYST]);
    }

    public function test_overview_returns_aggregated_totals(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-20 12:00:00'));

        AnalyticsDaily::factory()->create([
            'date' => '2026-03-10',
            'page_views' => 100,
            'unique_sessions' => 60,
            'whatsapp_clicks' => 20,
            'leads_created' => 10,
            'leads_qualified' => 5,
            'leads_won' => 2,
        ]);
        AnalyticsDaily::factory()->create([
            'date' => '2026-03-11',
            'page_views' => 50,
            'unique_sessions' => 30,
            'whatsapp_clicks' => 5,
            'leads_created' => 5,
            'leads_qualified' => 1,
            'leads_won' => 1,
        ]);

        $response = $this->actingAs($this->analyst())
            ->getJson('/admin/analytics/overview?from=2026-03-01&to=2026-03-15');

        $response->assertOk()
            ->assertJsonPath('data.totals.page_views', 150)
            ->assertJsonPath('data.totals.unique_sessions', 90)
            ->assertJsonPath('data.totals.whatsapp_clicks', 25)
            ->assertJsonPath('data.totals.leads_created', 15)
            ->assertJsonPath('data.totals.leads_qualified', 6)
            ->assertJsonPath('data.totals.leads_won', 3)
            ->assertJsonPath('data.partial', false);

        $this->assertCount(2, $response->json('data.daily'));
    }

    public function test_overview_ratios_are_computed(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-20 12:00:00'));

        AnalyticsDaily::factory()->create([
            'date' => '2026-03-10',
            'whatsapp_clicks' => 20,
            'leads_created' => 10,
            'leads_qualified' => 5,
            'leads_won' => 2,
        ]);

        $response = $this->actingAs($this->analyst())
            ->getJson('/admin/analytics/overview?from=2026-03-10&to=2026-03-10');

        $response->assertOk();
        $ratios = $response->json('data.ratios');

        $this->assertSame(0.5, $ratios['clicks_to_leads']);
        $this->assertSame(0.5, $ratios['leads_to_qualified']);
        $this->assertSame(0.4, $ratios['qualified_to_won']);
    }

    public function test_ratios_are_null_on_zero_denominators(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-20 12:00:00'));

        AnalyticsDaily::factory()->create([
            'date' => '2026-03-10',
            'whatsapp_clicks' => 0,
            'leads_created' => 0,
            'leads_qualified' => 0,
            'leads_won' => 0,
        ]);

        $response = $this->actingAs($this->analyst())
            ->getJson('/admin/analytics/overview?from=2026-03-10&to=2026-03-10');

        $response->assertOk();
        $ratios = $response->json('data.ratios');

        $this->assertNull($ratios['clicks_to_leads']);
        $this->assertNull($ratios['leads_to_qualified']);
        $this->assertNull($ratios['qualified_to_won']);

        $body = $response->getContent();
        $this->assertStringNotContainsString('INF', $body);
        $this->assertStringNotContainsString('NAN', $body);
    }

    public function test_overview_flags_today_as_partial_and_counts_live(): void
    {
        $today = CarbonImmutable::parse('2026-03-20 12:00:00');
        CarbonImmutable::setTestNow($today);

        AnalyticsDaily::factory()->create([
            'date' => '2026-03-19',
            'page_views' => 40,
            'whatsapp_clicks' => 4,
            'leads_created' => 2,
            'leads_qualified' => 1,
            'leads_won' => 1,
        ]);

        AnalyticsPageView::factory()->count(3)->create(['created_at' => $today->setTime(9, 0)]);
        WhatsAppClick::factory()->count(2)->create(['created_at' => $today->setTime(10, 0)]);
        Lead::factory()->count(2)->create(['created_at' => $today->setTime(11, 0)]);

        $response = $this->actingAs($this->analyst())
            ->getJson('/admin/analytics/overview?from=2026-03-19&to=2026-03-20');

        $response->assertOk()
            ->assertJsonPath('data.partial', true)
            ->assertJsonPath('data.totals.page_views', 43)
            ->assertJsonPath('data.totals.whatsapp_clicks', 6)
            ->assertJsonPath('data.totals.leads_created', 4);
    }

    public function test_overview_caches_within_ttl(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-20 12:00:00'));

        AnalyticsDaily::factory()->create([
            'date' => '2026-03-10',
            'page_views' => 10,
        ]);

        $analyst = $this->analyst();

        $this->actingAs($analyst)
            ->getJson('/admin/analytics/overview?from=2026-03-10&to=2026-03-10')
            ->assertOk();

        $this->assertNotNull(Cache::get('analytics:overview:2026-03-10:2026-03-10'));

        // Mutation after the first call must not be visible while cached.
        AnalyticsDaily::where('date', '2026-03-10')->update(['page_views' => 999]);

        $this->actingAs($analyst)
            ->getJson('/admin/analytics/overview?from=2026-03-10&to=2026-03-10')
            ->assertOk()
            ->assertJsonPath('data.totals.page_views', 10);
    }

    public function test_whatsapp_clicks_endpoint_returns_breakdown(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-20 12:00:00'));

        WhatsAppClick::factory()->count(2)->create([
            'page_type' => 'product',
            'device_category' => 'mobile',
            'created_at' => CarbonImmutable::parse('2026-03-10 09:00:00'),
        ]);
        WhatsAppClick::factory()->create([
            'page_type' => 'package',
            'device_category' => 'desktop',
            'created_at' => CarbonImmutable::parse('2026-03-10 10:00:00'),
        ]);

        $response = $this->actingAs($this->analyst())
            ->getJson('/admin/analytics/whatsapp-clicks?from=2026-03-10&to=2026-03-10');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
        $this->assertSame(2, $response->json('breakdown.by_page_type.product'));
        $this->assertSame(1, $response->json('breakdown.by_page_type.package'));
        $this->assertSame(2, $response->json('breakdown.by_device_category.mobile'));
        $this->assertSame(1, $response->json('breakdown.by_device_category.desktop'));
    }

    public function test_whatsapp_clicks_endpoint_filters_by_page_type(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-20 12:00:00'));

        WhatsAppClick::factory()->create([
            'page_type' => 'product',
            'created_at' => CarbonImmutable::parse('2026-03-10 09:00:00'),
        ]);
        WhatsAppClick::factory()->create([
            'page_type' => 'package',
            'created_at' => CarbonImmutable::parse('2026-03-10 09:00:00'),
        ]);

        $response = $this->actingAs($this->analyst())
            ->getJson('/admin/analytics/whatsapp-clicks?from=2026-03-10&to=2026-03-10&page_type=product');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('product', $response->json('data.0.page_type'));
    }

    public function test_leads_endpoint_returns_funnel_by_status(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-20 12:00:00'));

        Lead::factory()->count(3)->create([
            'status' => Lead::STATUS_NEW,
            'created_at' => CarbonImmutable::parse('2026-03-10 09:00:00'),
        ]);
        Lead::factory()->create([
            'status' => Lead::STATUS_WON,
            'created_at' => CarbonImmutable::parse('2026-03-10 10:00:00'),
        ]);

        $response = $this->actingAs($this->analyst())
            ->getJson('/admin/analytics/leads?from=2026-03-10&to=2026-03-10');

        $response->assertOk()
            ->assertJsonPath('data.by_status.new', 3)
            ->assertJsonPath('data.by_status.won', 1)
            ->assertJsonPath('data.funnel.created', 4)
            ->assertJsonPath('partial', false);
    }

    public function test_analyst_may_access_analytics(): void
    {
        $this->actingAs($this->analyst())
            ->getJson('/admin/analytics/overview')
            ->assertOk();
    }

    public function test_editor_is_forbidden(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $this->actingAs($editor)
            ->getJson('/admin/analytics/overview')
            ->assertForbidden();

        $this->actingAs($editor)
            ->getJson('/admin/analytics/whatsapp-clicks')
            ->assertForbidden();

        $this->actingAs($editor)
            ->getJson('/admin/analytics/leads')
            ->assertForbidden();
    }

    public function test_lead_manager_is_forbidden(): void
    {
        $leadManager = User::factory()->create(['role' => User::ROLE_LEAD_MANAGER]);

        $this->actingAs($leadManager)
            ->getJson('/admin/analytics/overview')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/analytics/overview')->assertRedirect('/login');
    }
}
