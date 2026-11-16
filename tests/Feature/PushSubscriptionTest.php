<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc-123',
            'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8QcYP7DkM',
            'auth' => 'tBHItJI5svbpez7KI4CCXg',
            'user_agent' => 'Mozilla/5.0 Test',
            'browser' => 'Chrome',
        ], $overrides);
    }

    public function test_user_can_subscribe_their_browser(): void
    {
        $user = User::factory()->administrator()->create();

        $response = $this->actingAs($user)
            ->postJson(route('admin.push-subscriptions.store'), $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.endpoint', 'https://fcm.googleapis.com/fcm/send/abc-123')
            ->assertJsonPath('data.active', true)
            ->assertJsonPath('data.browser', 'Chrome');

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc-123',
            'user_id' => $user->id,
            'browser' => 'Chrome',
            'active' => true,
        ]);
    }

    public function test_reposting_the_same_endpoint_upserts_instead_of_duplicating(): void
    {
        $user = User::factory()->administrator()->create();

        $this->actingAs($user)
            ->postJson(route('admin.push-subscriptions.store'), $this->payload())
            ->assertCreated();

        $response = $this->actingAs($user)->postJson(
            route('admin.push-subscriptions.store'),
            $this->payload(['p256dh' => 'BUpdatedKeyValue', 'browser' => 'Firefox']),
        );

        $response->assertOk();

        $this->assertSame(1, PushSubscription::count());
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc-123',
            'p256dh' => 'BUpdatedKeyValue',
            'browser' => 'Firefox',
        ]);
    }

    public function test_multiple_devices_store_separate_rows(): void
    {
        $user = User::factory()->administrator()->create();

        $this->actingAs($user)->postJson(route('admin.push-subscriptions.store'), $this->payload([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/device-one',
            'browser' => 'Chrome',
        ]))->assertCreated();

        $this->actingAs($user)->postJson(route('admin.push-subscriptions.store'), $this->payload([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/device-two',
            'browser' => 'Firefox',
        ]))->assertCreated();

        $this->assertSame(2, PushSubscription::where('user_id', $user->id)->count());
    }

    public function test_user_id_is_forced_from_the_session_not_the_payload(): void
    {
        $user = User::factory()->administrator()->create();
        $other = User::factory()->administrator()->create();

        $this->actingAs($user)->postJson(route('admin.push-subscriptions.store'), $this->payload([
            'user_id' => $other->id,
        ]))->assertCreated();

        $subscription = PushSubscription::firstOrFail();

        $this->assertSame($user->id, $subscription->user_id);
        $this->assertSame(0, PushSubscription::where('user_id', $other->id)->count());
    }

    public function test_index_only_lists_the_current_users_subscriptions(): void
    {
        $user = User::factory()->administrator()->create();
        $other = User::factory()->administrator()->create();

        PushSubscription::factory()->create(['user_id' => $user->id, 'endpoint' => 'https://example.test/mine']);
        PushSubscription::factory()->create(['user_id' => $other->id, 'endpoint' => 'https://example.test/theirs']);

        $response = $this->actingAs($user)->getJson(route('admin.push-subscriptions.index'));

        $response->assertOk();
        $endpoints = array_column($response->json('data'), 'endpoint');

        $this->assertSame(['https://example.test/mine'], $endpoints);
    }

    public function test_toggle_flips_the_active_flag(): void
    {
        $user = User::factory()->administrator()->create();
        $subscription = PushSubscription::factory()->create(['user_id' => $user->id, 'active' => true]);

        $this->actingAs($user)
            ->postJson(route('admin.push-subscriptions.toggle', $subscription))
            ->assertOk()
            ->assertJsonPath('data.active', false);

        $this->assertFalse($subscription->fresh()->active);

        $this->actingAs($user)
            ->postJson(route('admin.push-subscriptions.toggle', $subscription))
            ->assertOk()
            ->assertJsonPath('data.active', true);

        $this->assertTrue($subscription->fresh()->active);
    }

    public function test_destroy_removes_the_subscription(): void
    {
        $user = User::factory()->administrator()->create();
        $subscription = PushSubscription::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson(route('admin.push-subscriptions.destroy', $subscription))
            ->assertOk()
            ->assertJsonPath('message', 'Langganan notifikasi berhasil dihapus.');

        $this->assertDatabaseMissing('push_subscriptions', ['id' => $subscription->id]);
    }

    public function test_user_cannot_delete_another_users_subscription_without_manage_settings(): void
    {
        $owner = User::factory()->leadManager()->create();
        $attacker = User::factory()->leadManager()->create();
        $subscription = PushSubscription::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($attacker)
            ->deleteJson(route('admin.push-subscriptions.destroy', $subscription))
            ->assertForbidden();

        $this->assertDatabaseHas('push_subscriptions', ['id' => $subscription->id]);
    }

    public function test_analyst_is_forbidden_from_push_subscription_endpoints(): void
    {
        $analyst = User::factory()->analyst()->create();

        $this->actingAs($analyst)
            ->getJson(route('admin.push-subscriptions.index'))
            ->assertForbidden();

        $this->actingAs($analyst)
            ->postJson(route('admin.push-subscriptions.store'), $this->payload())
            ->assertForbidden();

        $this->assertSame(0, PushSubscription::count());
    }
}
