<?php

namespace Tests\Feature;

use App\Models\NotificationPreference;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Settings and notification screens.
 *
 * The critical assertions: the WhatsApp number round-trips through the same
 * whitelist as the JSON endpoint, retention maps onto the stored keys, the
 * private key never reaches the HTML, and nothing secret or emoji leaks.
 */
class AdminSettingsUiTest extends TestCase
{
    use RefreshDatabase;

    public static function deniedSettingsRoles(): array
    {
        return [
            'content_editor' => [User::ROLE_CONTENT_EDITOR],
            'lead_manager' => [User::ROLE_LEAD_MANAGER],
            'analyst' => [User::ROLE_ANALYST],
        ];
    }

    public function test_settings_page_renders_for_super_admin_with_current_values(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Setting::set('whatsapp_number', '6281234567890', $admin->id);
        Setting::set('vapaid_public_key', 'BPublicKeyOnlyValue', $admin->id);

        $response = $this->actingAs($admin)->get(route('admin.settings-ui.edit'));

        $response->assertOk()
            ->assertSee('6281234567890')
            ->assertSee('BPublicKeyOnlyValue')
            ->assertSee('Kunci privat tersimpan di berkas')
            ->assertSee('Masa simpan data')
            ->assertSee('90')
            ->assertSee('180')
            ->assertSee('365');
    }

    #[DataProvider('deniedSettingsRoles')]
    public function test_settings_page_is_forbidden_for_the_wrong_role(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->get(route('admin.settings-ui.edit'))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.settings-ui.update'), ['whatsapp_number' => '6281234567890'])
            ->assertForbidden();
    }

    public function test_whatsapp_number_update_persists(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings-ui.update'), ['whatsapp_number' => '+62 812-3456-7890'])
            ->assertRedirect(route('admin.settings-ui.edit'))
            ->assertSessionHas('status');

        $this->assertSame('6281234567890', Setting::get('whatsapp_number'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'settings.updated',
        ]);
    }

    public function test_retention_fields_map_onto_the_stored_keys(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings-ui.update'), [
                'retention' => [
                    'page_views_days' => 120,
                    'whatsapp_clicks_days' => 240,
                    'activity_logs_days' => 500,
                ],
            ])
            ->assertRedirect();

        $retention = Setting::get('retention');

        $this->assertSame(120, $retention['analytics_page_views']);
        $this->assertSame(240, $retention['whatsapp_clicks']);
        $this->assertSame(500, $retention['activity_logs']);
    }

    public function test_retention_below_the_floor_is_rejected(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings-ui.update'), [
                'retention' => [
                    'page_views_days' => 1,
                    'whatsapp_clicks_days' => 240,
                    'activity_logs_days' => 500,
                ],
            ])
            ->assertSessionHasErrors('retention.page_views_days');
    }

    public function test_private_key_fields_are_never_rendered(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Setting::set('vapaid_public_key', 'BPublicKeyOnlyValue', $admin->id);
        config(['vapaid.private_key' => 'SUPER-SECRET-PRIVATE-VALUE']);

        $html = $this->actingAs($admin)->get(route('admin.settings-ui.edit'))->getContent();

        $this->assertStringNotContainsString('SUPER-SECRET-PRIVATE-VALUE', $html);
        $this->assertStringNotContainsString('vapaid_private_key', $html);
        $this->assertStringNotContainsString('vapid_private_key', $html);
    }

    public function test_settings_page_contains_no_emoji_and_no_lorem(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $html = $this->actingAs($admin)->get(route('admin.settings-ui.edit'))->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}]/u',
            $html,
        );
        $this->assertStringNotContainsStringIgnoringCase('lorem', $html);
    }

    public function test_push_settings_renders_current_preferences(): void
    {
        $admin = User::factory()->administrator()->create();

        NotificationPreference::forUser($admin->id)->update([
            'whatsapp_clicks_enabled' => false,
            'lead_updates_enabled' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.push-settings.index'));

        $response->assertOk()
            ->assertSee('Preferensi pemberitahuan')
            ->assertSee('name="whatsapp_clicks_enabled"', false)
            ->assertSee('name="lead_updates_enabled"', false);
    }

    public function test_push_settings_never_renders_subscription_secrets(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $html = $this->actingAs($admin)->get(route('admin.push-settings.index'))->getContent();

        $this->assertStringNotContainsString('p256dh', $html);
        $this->assertStringNotContainsStringIgnoringCase('private_key', $html);
    }

    public function test_notification_preferences_update_persists(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->put(route('admin.push-settings.update'), [
                'whatsapp_clicks_enabled' => '0',
                'lead_updates_enabled' => '1',
            ])
            ->assertRedirect(route('admin.push-settings.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $admin->id,
            'whatsapp_clicks_enabled' => false,
            'lead_updates_enabled' => true,
        ]);
    }

    public function test_push_settings_requires_authentication(): void
    {
        $this->get(route('admin.push-settings.index'))->assertRedirect('/login');
    }
}
