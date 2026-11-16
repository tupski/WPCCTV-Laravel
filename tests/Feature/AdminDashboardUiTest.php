<?php

namespace Tests\Feature;

use App\Models\AnalyticsDaily;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsAppClick;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Server-rendered dashboard and analytics screens.
 *
 * Covers: role gating, labelled metrics with time windows, the follow-up
 * queue, and the zero-denominator funnel rule (an em dash, never "0%").
 */
class AdminDashboardUiTest extends TestCase
{
    use RefreshDatabase;

    public static function nonAdminRoles(): array
    {
        return [
            'administrator' => [User::ROLE_ADMINISTRATOR],
        ];
    }

    public static function deniedDashboardRoles(): array
    {
        return [
            'content_editor' => [User::ROLE_CONTENT_EDITOR],
            'lead_manager' => [User::ROLE_LEAD_MANAGER],
            'analyst' => [User::ROLE_ANALYST],
        ];
    }

    public static function deniedAnalyticsRoles(): array
    {
        return [
            'content_editor' => [User::ROLE_CONTENT_EDITOR],
            'lead_manager' => [User::ROLE_LEAD_MANAGER],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_dashboard_renders_for_allowed_roles(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Perlu ditindaklanjuti')
            ->assertSee('Klik WhatsApp terbaru')
            ->assertSee('Klik WhatsApp')
            ->assertSee('Lead dibuat')
            ->assertSee('Lead deal');
    }

    public function test_dashboard_renders_for_super_admin_with_data(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $click = WhatsAppClick::factory()->fromProduct()->create([
            'page_title' => 'Kamera Dome Indoor 2MP',
            'device_category' => 'mobile',
        ]);

        Lead::factory()->create([
            'customer_name' => 'Siti Rahmawati',
            'assigned_to' => $admin->id,
            'status' => Lead::STATUS_CONTACTED,
            'follow_up_at' => CarbonImmutable::today()->subDay(),
            'whatsapp_click_id' => $click->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Siti Rahmawati')
            ->assertSee('Kamera Dome Indoor 2MP')
            // A past due follow-up is stated as days overdue, not as a bare date.
            ->assertSee('Lewat 1 hari')
            // The metric table states its windows instead of leaving numbers bare.
            ->assertSee('Hari ini')
            ->assertSee('7 hari sampai');
    }

    #[DataProvider('deniedDashboardRoles')]
    public function test_dashboard_is_forbidden_for_the_wrong_role(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    #[DataProvider('nonAdminRoles')]
    public function test_analytics_renders_for_allowed_roles(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->get(route('admin.analytics-ui.index'))
            ->assertOk()
            ->assertSee('Corong konversi')
            ->assertSee('Klik WhatsApp per hari');
    }

    public function test_analytics_renders_for_analyst(): void
    {
        $user = User::factory()->analyst()->create();

        $this->actingAs($user)
            ->get(route('admin.analytics-ui.index', ['days' => 7]))
            ->assertOk()
            ->assertSee('7 hari');
    }

    #[DataProvider('deniedAnalyticsRoles')]
    public function test_analytics_is_forbidden_for_the_wrong_role(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->get(route('admin.analytics-ui.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.analytics-ui.whatsapp-clicks'))
            ->assertForbidden();
    }

    public function test_zero_denominator_funnel_step_renders_an_em_dash(): void
    {
        $analyst = User::factory()->analyst()->create();

        // Page views exist, but no WhatsApp click: every funnel denominator
        // after the first step is zero.
        AnalyticsDaily::create([
            'date' => CarbonImmutable::today()->subDay()->toDateString(),
            'page_views' => 12,
            'unique_sessions' => 9,
            'whatsapp_clicks' => 0,
            'leads_created' => 0,
            'leads_qualified' => 0,
            'leads_won' => 0,
        ]);

        $response = $this->actingAs($analyst)->get(route('admin.analytics-ui.index'));

        $response->assertOk()
            ->assertSee('>—</td>', false)
            ->assertDontSee('INF')
            ->assertDontSee('NaN');
    }

    public function test_whatsapp_click_report_breaks_down_page_type_and_device(): void
    {
        $analyst = User::factory()->analyst()->create();

        WhatsAppClick::factory()->fromProduct()->create([
            'page_title' => 'Kamera Outdoor 4MP',
            'device_category' => 'mobile',
        ]);

        $response = $this->actingAs($analyst)->get(route('admin.analytics-ui.whatsapp-clicks'));

        $response->assertOk()
            ->assertSee('Menurut jenis halaman')
            ->assertSee('Menurut perangkat')
            ->assertSee('Halaman produk')
            ->assertSee('Ponsel')
            ->assertSee('Kamera Outdoor 4MP');
    }

    public function test_activity_log_page_renders_as_a_table(): void
    {
        $leadManager = User::factory()->leadManager()->create();

        \App\Models\ActivityLog::create([
            'user_id' => $leadManager->id,
            'action' => 'lead.status_changed',
            'subject_type' => Lead::class,
            'subject_id' => 1,
            'context' => ['old_value' => 'new', 'new_value' => 'contacted'],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($leadManager)->get(route('admin.activity-logs-ui.index'));

        $response->assertOk()
            ->assertSee('Log aktivitas')
            ->assertSee('<th scope="col"', false)
            ->assertSee('Pelaku')
            ->assertSee('Tindakan')
            ->assertSee('Status lead diubah')
            ->assertSee('Lead #1');
    }

    public function test_activity_log_page_is_forbidden_for_content_editor(): void
    {
        $editor = User::factory()->contentEditor()->create();

        $this->actingAs($editor)
            ->get(route('admin.activity-logs-ui.index'))
            ->assertForbidden();
    }

    public function test_nav_hides_analytics_from_lead_manager(): void
    {
        $leadManager = User::factory()->leadManager()->create();

        $response = $this->actingAs($leadManager)->get(route('admin.leads-ui.index'));

        $response->assertOk()
            ->assertSee(route('admin.leads-ui.index'), false)
            ->assertDontSee(route('admin.analytics-ui.index'), false);
    }

    public function test_nav_hides_leads_from_analyst(): void
    {
        $analyst = User::factory()->analyst()->create();

        $response = $this->actingAs($analyst)->get(route('admin.analytics-ui.index'));

        $response->assertOk()
            ->assertSee(route('admin.analytics-ui.index'), false)
            ->assertDontSee(route('admin.leads-ui.index'), false)
            ->assertDontSee(route('admin.settings-ui.edit'), false);
    }

    public function test_nav_shows_every_module_to_super_admin(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee(route('admin.leads-ui.index'), false)
            ->assertSee(route('admin.analytics-ui.index'), false)
            ->assertSee(route('admin.activity-logs-ui.index'), false)
            ->assertSee(route('admin.settings-ui.edit'), false);
    }

    public function test_rendered_dashboard_contains_no_emoji_and_no_placeholder_copy(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}]/u',
            $html,
            'Dasbor tidak boleh memuat emoji.',
        );

        $this->assertStringNotContainsStringIgnoringCase('lorem', $html);
    }
}
