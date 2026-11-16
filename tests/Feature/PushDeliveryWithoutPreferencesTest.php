<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Models\User;
use App\Models\WhatsAppClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\WebPush;
use Tests\TestCase;

/**
 * Regression coverage for PHASE_4_CLOSURE_AUDIT.md H1.
 *
 * Root cause confirmed at app/Jobs/SendWhatsAppNotificationJob.php:85-87 — the
 * job derived recipients from `notification_preferences`
 * (`whereIn('id', NotificationPreference::where(...)->pluck('user_id'))`), so a
 * user with no stored row could never be a recipient. Browser notifications are
 * mandatory (PRD §8, L517) but only began working after an admin happened to
 * open the preferences screen, which created the row as a side effect.
 */
class PushDeliveryWithoutPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function configureValidVapid(): void
    {
        Config::set('vapaid.public_key', base64_encode(str_repeat('A', 65)));
        Config::set('vapaid.private_key', base64_encode(str_repeat('B', 32)));
        Config::set('vapaid.subject', 'mailto:admin@wpcctv.id');
    }

    /**
     * The regression the audit could not detect: an admin with ZERO preference
     * rows plus an active subscription must still receive a delivery attempt.
     */
    public function test_delivery_is_attempted_for_an_admin_with_no_preference_row(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);
        $subscription = PushSubscription::factory()->create(['user_id' => $admin->id]);
        $click = WhatsAppClick::factory()->create(['page_title' => 'Kamera']);

        $this->assertSame(
            0,
            NotificationPreference::count(),
            'Precondition: the preferences table is empty, as it is on a fresh install.'
        );

        $this->configureValidVapid();

        $sent = [];
        $pusher = \Mockery::mock(WebPush::class);
        $pusher->shouldReceive('sendOneNotification')
            ->andReturnUsing(function ($sub, $payload) use (&$sent) {
                $sent[] = $sub->getEndpoint();

                return new MessageSentReport(
                    request: new \GuzzleHttp\Psr7\Request('GET', 'https://fcm.googleapis.com'),
                    response: new \GuzzleHttp\Psr7\Response(201),
                );
            });

        // Replace the protected webPush() result via a subclass so no network
        // call is made; handle() itself remains the real implementation.
        $job = new class($click, $pusher) extends SendWhatsAppNotificationJob
        {
            public function __construct(WhatsAppClick $click, private WebPush $fakePusher)
            {
                parent::__construct($click);
            }

            protected function webPush(): ?WebPush
            {
                return $this->fakePusher;
            }
        };

        $job->handle();

        $this->assertCount(1, $sent, 'Delivery must be attempted even with no stored preference row.');
        $this->assertSame($subscription->endpoint, $sent[0]);
        $this->assertSame(
            0,
            NotificationPreference::count(),
            'Reading preferences must not create a row as a side effect.'
        );
    }

    /**
     * forUser() must return default-enabled values without touching the DB.
     */
    public function test_for_user_returns_defaults_without_persisting(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        $preferences = NotificationPreference::forUser($admin->id);

        $this->assertTrue($preferences->whatsapp_clicks_enabled);
        $this->assertTrue($preferences->lead_updates_enabled);
        $this->assertSame($admin->id, (int) $preferences->user_id);
        $this->assertFalse($preferences->exists, 'forUser() must not persist on read.');
        $this->assertSame(0, NotificationPreference::count());
    }

    /**
     * An explicit stored opt-out must still be honoured — the default-enabled
     * change must not make the toggle ineffective.
     */
    public function test_explicit_opt_out_still_suppresses_delivery(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);
        PushSubscription::factory()->create(['user_id' => $admin->id]);
        NotificationPreference::factory()->create([
            'user_id' => $admin->id,
            'whatsapp_clicks_enabled' => false,
        ]);
        $click = WhatsAppClick::factory()->create(['page_title' => 'Kamera']);

        $this->configureValidVapid();

        $pusher = \Mockery::mock(WebPush::class);
        $pusher->shouldReceive('sendOneNotification')->never();

        $job = new class($click, $pusher) extends SendWhatsAppNotificationJob
        {
            public function __construct(WhatsAppClick $click, private WebPush $fakePusher)
            {
                parent::__construct($click);
            }

            protected function webPush(): ?WebPush
            {
                return $this->fakePusher;
            }
        };

        $job->handle();
    }

    /**
     * A non-admin with no preference row must not become a recipient.
     */
    public function test_non_admins_are_still_excluded_when_no_rows_exist(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        PushSubscription::factory()->create(['user_id' => $editor->id]);
        $click = WhatsAppClick::factory()->create(['page_title' => 'Kamera']);

        $this->configureValidVapid();

        $pusher = \Mockery::mock(WebPush::class);
        $pusher->shouldReceive('sendOneNotification')->never();

        $job = new class($click, $pusher) extends SendWhatsAppNotificationJob
        {
            public function __construct(WhatsAppClick $click, private WebPush $fakePusher)
            {
                parent::__construct($click);
            }

            protected function webPush(): ?WebPush
            {
                return $this->fakePusher;
            }
        };

        $job->handle();
    }
}
