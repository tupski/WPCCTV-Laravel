<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\AnalyticsPageView;
use App\Models\Setting;
use App\Models\WhatsAppClick;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled daily: purge historical data beyond configured retention tiers.
 *
 * Tiers (days) come from `Setting::get('retention')` with defaults:
 *   analytics_page_views → 90
 *   whatsapp_clicks      → 180
 *   activity_logs        → 365
 *
 * [PROVISIONAL] — durations confirmed by the project owner in the plan.
 *
 * Retry policy (audit M1):
 *
 * `$tries = 3` with a 60/300-second backoff. A purge is a bulk DELETE, so the
 * realistic failure is a transient one — a deadlock, a lock-wait timeout, or a
 * lost connection — and those clear within seconds. A 300-second ceiling on the
 * last attempt keeps the retry window to roughly six minutes so a nightly run
 * cannot still be retrying when the next one starts. Not `tries = 1`: a single
 * transient lock error would silently stop retention enforcement until the next
 * night, which is a compliance-facing failure.
 *
 * `$timeout = 120` fits a shared-hosting worker, whose PHP `max_execution_time`
 * is commonly 120s. The purge is chunked (below) so it finishes well inside
 * that; the timeout is the backstop, not the budget.
 *
 * Idempotent by construction: each tier deletes rows where `created_at` is
 * older than a cutoff derived from *now*. Running it twice deletes the same
 * set the second time (0 rows). `tests/Feature/RetentionJobTest.php` asserts
 * exactly that.
 */
class RetentionCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const DEFAULT_TIERS = [
        'analytics_page_views' => 90,
        'whatsapp_clicks' => 180,
        'activity_logs' => 365,
    ];

    /**
     * Rows deleted per statement.
     *
     * The previous implementation issued three unbounded DELETEs. On a table
     * that has never been purged, `activity_logs` can hold hundreds of
     * thousands of rows; one DELETE over all of them is a single long-lived
     * lock and buffers every affected row in memory. Chunking bounds both the
     * lock duration and the memory high-water mark, and keeps every statement
     * well inside `$timeout`.
     */
    public const CHUNK_SIZE = 1000;

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
    public $backoff = [60, 300];

    /**
     * Seconds a single attempt may run.
     *
     * @var int
     */
    public $timeout = 120;

    public function handle(): void
    {
        $tiers = array_merge(
            self::DEFAULT_TIERS,
            (array) Setting::get('retention', [])
        );

        $cutoffViews = now()->subDays($tiers['analytics_page_views']);
        $cutoffClicks = now()->subDays($tiers['whatsapp_clicks']);
        $cutoffLogs = now()->subDays($tiers['activity_logs']);

        $viewsPurged = $this->purge(AnalyticsPageView::class, $cutoffViews);
        $clicksPurged = $this->purge(WhatsAppClick::class, $cutoffClicks);
        $logsPurged = $this->purge(ActivityLog::class, $cutoffLogs);

        Log::info("RetentionCleanupJob: hapus {$viewsPurged} analytics_page_views, {$clicksPurged} whatsapp_clicks, {$logsPurged} activity_logs.");
    }

    /**
     * Delete rows older than `$cutoff` in bounded chunks.
     *
     * Each chunk is its own statement, so the caller holds a short lock rather
     * than one spanning the whole table. The predicate is stable across chunks
     * (`created_at < $cutoff`, and every deleted row stops matching), so the
     * loop always terminates — even if new rows are inserted concurrently,
     * they carry `created_at = now()` and never satisfy the cutoff.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    protected function purge(string $model, \DateTimeInterface $cutoff): int
    {
        $deleted = 0;

        do {
            $affected = $model::query()
                ->where('created_at', '<', $cutoff)
                ->limit(self::CHUNK_SIZE)
                ->delete();

            $deleted += $affected;
        } while ($affected === self::CHUNK_SIZE);

        return $deleted;
    }
}
