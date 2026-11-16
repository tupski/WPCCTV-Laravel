<?php

namespace Tests\Feature;

use App\Jobs\ExpirePushSubscriptionsJob;
use App\Jobs\RetentionCleanupJob;
use App\Models\ActivityLog;
use App\Models\AnalyticsPageView;
use App\Models\PushSubscription;
use App\Models\User;
use App\Models\WhatsAppClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit M1: the purge and expiry jobs ran without retry bounds.
 *
 * Two properties are asserted per job:
 *  - retry policy (`$tries`, `$backoff`, `$timeout`) is explicit and sane for a
 *    shared-hosting worker;
 *  - the job is idempotent, which matters more than the retries — a second run
 *    must produce the same end state.
 */
class JobRetryPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_retention_job_declares_bounded_retries_backoff_and_timeout(): void
    {
        $job = new RetentionCleanupJob();

        $this->assertSame(3, $job->tries);
        $this->assertSame([60, 300], $job->backoff);
        $this->assertSame(120, $job->timeout);

        // Shared-hosting queue workers commonly run with a 120s
        // max_execution_time; a timeout above that is not a budget, it is a
        // process kill, so the ceiling is asserted rather than assumed.
        $this->assertLessThanOrEqual(120, $job->timeout);
    }

    public function test_expire_job_declares_bounded_retries_backoff_and_timeout(): void
    {
        $job = new ExpirePushSubscriptionsJob();

        $this->assertSame(3, $job->tries);
        $this->assertSame([30, 120], $job->backoff);
        $this->assertSame(60, $job->timeout);
        $this->assertLessThanOrEqual(120, $job->timeout);
    }

    public function test_retention_job_is_idempotent_across_two_runs(): void
    {
        $user = User::factory()->administrator()->create();

        // Two rows inside each tier, one row outside each tier.
        AnalyticsPageView::factory()->create(['created_at' => now()->subDays(200)]);
        WhatsAppClick::factory()->create(['created_at' => now()->subDays(400)]);
        ActivityLog::factory()->create(['created_at' => now()->subDays(500)]);

        AnalyticsPageView::factory()->create(['created_at' => now()->subDay()]);
        WhatsAppClick::factory()->create(['created_at' => now()->subDay()]);
        ActivityLog::factory()->create(['created_at' => now()->subDay()]);

        (new RetentionCleanupJob())->handle();

        $afterFirst = [
            AnalyticsPageView::count(),
            WhatsAppClick::count(),
            ActivityLog::count(),
        ];

        (new RetentionCleanupJob())->handle();

        $afterSecond = [
            AnalyticsPageView::count(),
            WhatsAppClick::count(),
            ActivityLog::count(),
        ];

        $this->assertSame([1, 1, 1], $afterFirst, 'Each tier keeps only the in-retention row.');
        $this->assertSame($afterFirst, $afterSecond, 'A second run must be a no-op, not a further deletion.');
    }

    public function test_expire_job_is_idempotent_across_two_runs(): void
    {
        $user = User::factory()->administrator()->create();

        $expired = PushSubscription::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->subDay(),
            'active' => true,
        ]);

        $stale = PushSubscription::factory()->create([
            'user_id' => $user->id,
            'active' => false,
        ]);
        $stale->forceFill(['updated_at' => now()->subDays(60)])->save();

        $fresh = PushSubscription::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addMonth(),
            'active' => true,
        ]);

        (new ExpirePushSubscriptionsJob())->handle();

        $this->assertFalse($expired->fresh()->active);
        $this->assertNotNull($fresh->fresh(), 'A live subscription must survive.');
        $this->assertTrue($fresh->fresh()->active);
        $this->assertNull(PushSubscription::find($stale->id), 'A stale inactive row is purged.');

        $snapshot = PushSubscription::query()->orderBy('id')->pluck('active', 'id')->all();

        (new ExpirePushSubscriptionsJob())->handle();

        $this->assertSame(
            $snapshot,
            PushSubscription::query()->orderBy('id')->pluck('active', 'id')->all(),
            'A second run must not change any row.'
        );
    }

    /**
     * A reactivated subscription is not re-expired by a later run: the UPDATE
     * only matches rows that are still `active` AND already past `expires_at`.
     */
    public function test_expire_job_does_not_re_expire_a_reactivated_subscription(): void
    {
        $user = User::factory()->administrator()->create();

        $sub = PushSubscription::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->subDay(),
            'active' => true,
        ]);

        (new ExpirePushSubscriptionsJob())->handle();
        $this->assertFalse($sub->fresh()->active);

        // Reactivated AND given a future expiry, written straight to the row so
        // the change is unambiguous (no model dirty-tracking in the way). The
        // assertion then holds only because the UPDATE matches on *both*
        // `active` and `expires_at`.
        PushSubscription::whereKey($sub->id)->update([
            'active' => true,
            'expires_at' => now()->addMonth(),
        ]);

        $this->assertTrue($sub->fresh()->active, 'Premise: the row is active and unexpired again.');
        $this->assertTrue($sub->fresh()->expires_at->isFuture(), 'Premise: the expiry was moved forward.');

        (new ExpirePushSubscriptionsJob())->handle();

        $this->assertTrue($sub->fresh()->active);
    }

    /**
     * The purge is chunked, so a backlog larger than one chunk is fully cleared.
     */
    public function test_retention_job_purges_beyond_a_single_chunk(): void
    {
        $count = RetentionCleanupJob::CHUNK_SIZE + 5;

        for ($i = 0; $i < $count; $i++) {
            AnalyticsPageView::factory()->create(['created_at' => now()->subDays(200)]);
        }

        $this->assertSame($count, AnalyticsPageView::count());

        (new RetentionCleanupJob())->handle();

        $this->assertSame(0, AnalyticsPageView::count(), 'Every chunk must be drained, not just the first.');
    }
}
