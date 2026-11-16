<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->administrator()->create();
    }

    public function test_index_returns_current_settings_with_defaults(): void
    {
        Setting::set('whatsapp_number', '6281234567890');
        Setting::set('vapaid_public_key', 'public-key-value');

        $response = $this->actingAs($this->admin())->getJson('/admin/settings');

        $response->assertOk()
            ->assertJsonPath('data.whatsapp_number', '6281234567890')
            ->assertJsonPath('data.vapaid_public_key', 'public-key-value')
            ->assertJsonPath('data.retention.page_views_days', 90)
            ->assertJsonPath('data.retention.whatsapp_clicks_days', 180)
            ->assertJsonPath('data.retention.activity_logs_days', 365);
    }

    public function test_index_never_returns_private_keys(): void
    {
        Setting::set('vapaid_private_key', 'super-private');

        $response = $this->actingAs($this->admin())->getJson('/admin/settings');

        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringNotContainsString('vapaid_private_key', $body);
        $this->assertStringNotContainsString('super-private', $body);
    }

    public function test_update_persists_whitelisted_keys(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->putJson('/admin/settings', [
            'whatsapp_number' => '6281234567890',
            'retention' => [
                'page_views_days' => 30,
                'whatsapp_clicks_days' => 60,
                'activity_logs_days' => 120,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.whatsapp_number', '6281234567890')
            ->assertJsonPath('data.retention.page_views_days', 30);

        $this->assertSame('6281234567890', Setting::get('whatsapp_number'));

        $retention = Setting::get('retention');
        $this->assertSame(30, $retention['analytics_page_views']);
        $this->assertSame(60, $retention['whatsapp_clicks']);
        $this->assertSame(120, $retention['activity_logs']);

        $setting = Setting::where('key', 'whatsapp_number')->first();
        $this->assertSame($admin->id, $setting->updated_by);
    }

    public function test_update_writes_an_activity_log(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->putJson('/admin/settings', [
            'whatsapp_number' => '6281234567890',
        ])->assertOk();

        $log = ActivityLog::where('action', 'settings.updated')->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertContains('whatsapp_number', $log->context['changed']);
    }

    public function test_private_key_cannot_be_written(): void
    {
        $this->actingAs($this->admin())
            ->putJson('/admin/settings', [
                'vapaid_private_key' => 'attacker-supplied',
            ])
            ->assertStatus(422);

        $this->assertNull(Setting::get('vapaid_private_key'));
    }

    public function test_non_whitelisted_keys_are_ignored(): void
    {
        $this->actingAs($this->admin())
            ->putJson('/admin/settings', [
                'whatsapp_number' => '6281234567890',
                'arbitrary_key' => 'should-not-persist',
            ])
            ->assertOk();

        $this->assertNull(Setting::where('key', 'arbitrary_key')->first());
    }

    public function test_whatsapp_number_rejects_malformed_input(): void
    {
        $admin = $this->admin();

        foreach (['abc123', '1234567', '1234567890123456', '+ -', 'call-me'] as $invalid) {
            $this->actingAs($admin)
                ->putJson('/admin/settings', ['whatsapp_number' => $invalid])
                ->assertStatus(422);
        }

        $this->assertNull(Setting::get('whatsapp_number'));
    }

    public function test_whatsapp_number_strips_non_digits(): void
    {
        $this->actingAs($this->admin())
            ->putJson('/admin/settings', ['whatsapp_number' => '+62 812-3456-7890'])
            ->assertOk()
            ->assertJsonPath('data.whatsapp_number', '6281234567890');

        $this->assertSame('6281234567890', Setting::get('whatsapp_number'));
    }

    public function test_whatsapp_number_can_be_cleared(): void
    {
        Setting::set('whatsapp_number', '6281234567890');

        $this->actingAs($this->admin())
            ->putJson('/admin/settings', ['whatsapp_number' => null])
            ->assertOk();

        $this->assertNull(Setting::get('whatsapp_number'));
    }

    public function test_retention_bounds_are_enforced(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->putJson('/admin/settings', [
            'retention' => [
                'page_views_days' => 6,
                'whatsapp_clicks_days' => 60,
                'activity_logs_days' => 120,
            ],
        ])->assertStatus(422);

        $this->actingAs($admin)->putJson('/admin/settings', [
            'retention' => [
                'page_views_days' => 30,
                'whatsapp_clicks_days' => 60,
                'activity_logs_days' => 3651,
            ],
        ])->assertStatus(422);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $this->actingAs($editor)->getJson('/admin/settings')->assertForbidden();
        $this->actingAs($editor)
            ->putJson('/admin/settings', ['whatsapp_number' => '6281234567890'])
            ->assertForbidden();
    }

    public function test_analyst_is_forbidden(): void
    {
        $analyst = User::factory()->create(['role' => User::ROLE_ANALYST]);

        $this->actingAs($analyst)->getJson('/admin/settings')->assertForbidden();
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/admin/settings')->assertRedirect('/login');
    }
}
