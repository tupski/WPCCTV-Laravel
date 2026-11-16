<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled daily: mark expired push subscriptions inactive and purge
 * inactive subscriptions older than 30 days [PROVISIONAL].
 *
 * Retry policy (audit M1):
 *
 * `$tries = 3` with a 30/120-second backoff. This job touches a small table
 * (one row per browser install), so it fails fast and retries quickly — there
 * is no long-running statement to wait out, only deadlock/lock-wait or
 * connection blips. The shorter backoff than RetentionCleanupJob reflects that.
 *
 * `$timeout = 60` is comfortably inside a shared-hosting worker's usual 120s
 * `max_execution_time`, and generous for an UPDATE plus a DELETE over a table
 * of this size.
 *
 * Idempotent by construction, and this matters more than the retries:
 *  - the UPDATE only matches rows that are *still* active and already expired,
 *    so a second run matches nothing (0 rows) and cannot re-flip a row an
 *    administrator reactivated in between;
 *  - the DELETE only matches rows that are inactive and untouched for 30 days,
 *    and deleting a row twice is impossible.
 * `tests/Feature/NotificationJobTest.php` asserts the two-run invariant.
 */
class ExpirePushSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Total attempts before the job is failed.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Seconds to wait before each retry.
     *
     * @var array<int, int>
     */
    public $backoff = [30, 120];

    /**
     * Seconds a single attempt may run.
     *
     * @var int
     */
    public $timeout = 60;

    public function handle(): void
    {
        $expired = PushSubscription::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->where('active', true)
            ->update(['active' => false]);

        $purged = PushSubscription::where('active', false)
            ->where('updated_at', '<', now()->subDays(30))
            ->delete();

        Log::info("ExpirePushSubscriptionsJob: {$expired} expired ditandai tidak aktif, {$purged} bersejarah dihapus.");
    }
}
