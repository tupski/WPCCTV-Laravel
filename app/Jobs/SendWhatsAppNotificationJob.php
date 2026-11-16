<?php

namespace App\Jobs;

use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppClick;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Backoff (seconds) between retries.
     *
     * @var int[]
     */
    public array $backoff = [30, 60, 120];

    public function __construct(public WhatsAppClick $click)
    {
    }

    /**
     * Resolve the minishlink WebPush instance, or null when VAPID keys are
     * missing. Push is disabled in that case — never throw.
     */
    protected function webPush(): ?WebPush
    {
        $vapid = $this->vapid();

        if ($vapid === null) {
            Log::warning('Push notification skipped: VAPID keys not configured.');

            return null;
        }

        return new WebPush(['VAPID' => $vapid]);
    }

    /**
     * Assemble VAPID auth from env (private) + settings table (public).
     * Returns null when the private key is absent.
     */
    protected function vapid(): ?array
    {
        $publicKey = config('vapaid.public_key') ?: Setting::get('vapaid_public_key');
        $privateKey = config('vapaid.private_key');
        $subject = config('vapaid.subject');

        if (blank($publicKey) || blank($privateKey)) {
            return null;
        }

        return [
            'subject' => $subject,
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
        ];
    }

    public function handle(): void
    {
        $pusher = $this->webPush();

        if ($pusher === null) {
            return;
        }

        // Select admins from the users table only. Deriving candidates from
        // notification_preferences (the previous `whereIn('id', <pluck>)`)
        // meant an empty preferences table produced zero recipients, so push
        // silently never fired until an admin happened to open the settings
        // screen (audit H1). Absence of a row now means "enabled by default";
        // only an explicit stored false opts a user out.
        $admins = User::admin()->get();

        if ($admins->isEmpty()) {
            return;
        }

        $optedOut = NotificationPreference::whatsappClicksOptOuts($admins->modelKeys());

        $users = $admins->reject(fn (User $user): bool => in_array($user->id, $optedOut, true));

        if ($users->isEmpty()) {
            return;
        }

        $payload = json_encode($this->payload());

        foreach ($users as $user) {
            $subscriptions = PushSubscription::where('user_id', $user->id)->active()->get();

            foreach ($subscriptions as $subscription) {
                $this->send($pusher, $subscription, $payload);
            }
        }
    }

    /**
     * Build the push payload. NO PII in preview: no customer name, no phone
     * number, no IP. Only event metadata.
     */
    protected function payload(): array
    {
        return [
            'title' => 'New WhatsApp Inquiry',
            'body' => 'Visitor clicked WhatsApp from '.($this->click->page_title ?: 'situs'),
            'url' => url('/admin/leads'),
            'event_id' => $this->click->event_id,
        ];
    }

    protected function send(WebPush $pusher, PushSubscription $model, string $payload): void
    {
        $subscription = new Subscription(
            endpoint: $model->endpoint,
            publicKey: $model->p256dh,
            authToken: $model->auth,
        );

        $auth = $this->vapid() !== null ? $this->vapid() : [];

        try {
            $report = $pusher->sendOneNotification($subscription, $payload, [], $auth);
        } catch (\Throwable $e) {
            // Network failure → let Laravel retry. On final attempt, mark inactive.
            if ($this->attempts() >= $this->tries) {
                $model->update(['active' => false]);
                Log::error("Push delivery gagal permanen untuk subscription {$model->id}: {$e->getMessage()}");
            }

            throw $e;
        }

        if ($report->isSubscriptionExpired()) {
            $model->update(['active' => false]);
            Log::info("Push subscription {$model->id} kadaluarsa (HTTP 404/410); ditandai tidak aktif.");
        }
    }
}
