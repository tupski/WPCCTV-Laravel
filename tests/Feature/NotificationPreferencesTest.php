<?php

namespace Tests\Feature;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_creates_default_preferences_for_the_user(): void
    {
        $user = User::factory()->administrator()->create();

        $response = $this->actingAs($user)->getJson(route('admin.notification-preferences.index'));

        $response->assertOk()
            ->assertJsonPath('data.whatsapp_clicks_enabled', true)
            ->assertJsonPath('data.lead_updates_enabled', true);

        $this->assertDatabaseHas('notification_preferences', ['user_id' => $user->id]);
    }

    public function test_user_can_disable_notifications(): void
    {
        $user = User::factory()->administrator()->create();

        $response = $this->actingAs($user)->putJson(route('admin.notification-preferences.update'), [
            'whatsapp_clicks_enabled' => false,
            'lead_updates_enabled' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.whatsapp_clicks_enabled', false)
            ->assertJsonPath('data.lead_updates_enabled', false);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'whatsapp_clicks_enabled' => false,
            'lead_updates_enabled' => false,
        ]);
    }

    public function test_user_id_is_forced_from_the_session_not_the_payload(): void
    {
        $user = User::factory()->administrator()->create();
        $other = User::factory()->administrator()->create();

        $this->actingAs($user)->putJson(route('admin.notification-preferences.update'), [
            'user_id' => $other->id,
            'whatsapp_clicks_enabled' => false,
        ])->assertOk();

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'whatsapp_clicks_enabled' => false,
        ]);

        $this->assertSame(0, NotificationPreference::where('user_id', $other->id)->count());
    }

    public function test_analyst_is_forbidden_from_notification_preferences(): void
    {
        $analyst = User::factory()->analyst()->create();

        $this->actingAs($analyst)
            ->getJson(route('admin.notification-preferences.index'))
            ->assertForbidden();
    }
}
