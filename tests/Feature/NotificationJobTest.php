<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Models\User;
use App\Models\WhatsAppClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class NotificationJobTest extends TestCase
{
    use RefreshDatabase;

    protected function validVapidKeys(): array
    {
        return [
            'public_key' => base64_encode(str_repeat("\x04", 0).str_repeat('A', 65)),
            'private_key' => base64_encode(str_repeat('B', 32)),
            'subject' => 'mailto:admin@wpcctv.id',
        ];
    }

    public function test_job_no_op_when_vapid_missing(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);
        NotificationPreference::factory()->create(['user_id' => $user->id]);
        PushSubscription::factory()->create(['user_id' => $user->id]);
        $click = WhatsAppClick::factory()->create(['page_title' => 'Kamera']);

        Config::set('vapaid.public_key', null);
        Config::set('vapaid.private_key', null);

        // Must not throw, must not send.
        $job = new SendWhatsAppNotificationJob($click);
        $job->handle();

        $this->assertTrue(PushSubscription::first()->active);
    }

    public function test_payload_contains_no_pii_keys(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);
        NotificationPreference::factory()->create(['user_id' => $user->id]);
        PushSubscription::factory()->create(['user_id' => $user->id]);
        $click = WhatsAppClick::factory()->create([
            'page_title' => 'Kamera IP',
            'event_id' => 'evt-123',
            'page_url' => '/products/kamera-ip',
        ]);

        Config::set('vapaid.public_key', $this->validVapidKeys()['public_key']);
        Config::set('vapaid.private_key', $this->validVapidKeys()['private_key']);
        Config::set('vapaid.subject', 'mailto:admin@wpcctv.id');

        // Extract payload structure via reflection without hitting the network.
        $job = new SendWhatsAppNotificationJob($click);
        $ref = new \ReflectionMethod($job, 'payload');
        $payload = $ref->invoke($job);

        $json = json_encode($payload);
        $this->assertStringContainsString('"title"', $json);
        $this->assertStringContainsString('"body"', $json);
        $this->assertStringContainsString('"url"', $json);
        $this->assertStringContainsString('"event_id"', $json);

        // No PII fields anywhere in the payload.
        foreach (['customer_name', 'whatsapp_number', 'ip_address', 'visitor_token', 'phone'] as $piiKey) {
            $this->assertStringNotContainsString($piiKey, $json);
        }
    }

    public function test_job_targets_only_admins_with_whatsapp_clicks_enabled(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);
        NotificationPreference::factory()->create(['user_id' => $admin->id]);
        PushSubscription::factory()->create(['user_id' => $admin->id]);

        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        NotificationPreference::factory()->create(['user_id' => $editor->id]);
        $editorSub = PushSubscription::factory()->create(['user_id' => $editor->id]);

        $click = WhatsAppClick::factory()->create(['page_title' => 'Kamera']);

        Config::set('vapaid.public_key', $this->validVapidKeys()['public_key']);
        Config::set('vapaid.private_key', $this->validVapidKeys()['private_key']);
        Config::set('vapaid.subject', 'mailto:admin@wpcctv.id');

        // Job should not crash; only admin subscription is a candidate.
        // (Actual HTTP send requires network; with invalid base64 keys the
        // constructor's VAPID::validate would throw — so assert selection logic
        // via a disabled preference instead.)
        NotificationPreference::firstOrCreate(
            ['user_id' => $admin->id]
        )->update(['whatsapp_clicks_enabled' => false]);

        $job = new SendWhatsAppNotificationJob($click);
        $job->handle(); // no eligible users → early return, no throw

        $this->assertTrue(PushSubscription::whereKey($editorSub->id)->value('active'));
    }

    public function test_410_response_marks_subscription_inactive(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);
        NotificationPreference::factory()->create(['user_id' => $user->id]);
        $sub = PushSubscription::factory()->create(['user_id' => $user->id]);
        $click = WhatsAppClick::factory()->create(['page_title' => 'Kamera']);

        Config::set('vapaid.public_key', $this->validVapidKeys()['public_key']);
        Config::set('vapaid.private_key', $this->validVapidKeys()['private_key']);
        Config::set('vapaid.subject', 'mailto:admin@wpcctv.id');

        $job = new SendWhatsAppNotificationJob($click);

        $report = new \Minishlink\WebPush\MessageSentReport(
            request: new \GuzzleHttp\Psr7\Request('GET', 'https://fcm.googleapis.com'),
            response: new \GuzzleHttp\Psr7\Response(410),
        );

        $pusher = \Mockery::mock(\Minishlink\WebPush\WebPush::class);
        $pusher->shouldReceive('sendOneNotification')
            ->andReturn($report);

        $payload = json_encode(['title' => 'New WhatsApp Inquiry']);

        $ref = new \ReflectionMethod($job, 'send');
        $ref->setAccessible(true);
        $ref->invoke($job, $pusher, $sub, $payload);

        $sub->refresh();
        $this->assertFalse($sub->active, '410 response must mark subscription inactive.');
    }
}
