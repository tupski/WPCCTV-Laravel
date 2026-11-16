<?php

namespace Tests\Feature;

use App\Jobs\ExpirePushSubscriptionsJob;
use App\Jobs\RetentionCleanupJob;
use App\Models\ActivityLog;
use App\Models\AnalyticsPageView;
use App\Models\PushSubscription;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetentionJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_retention_purges_by_default_tiers(): void
    {
        // Outside 90d tier
        AnalyticsPageView::factory()->create(['created_at' => now()->subDays(91)]);
        // Inside 90d tier
        AnalyticsPageView::factory()->create(['created_at' => now()->subDays(10)]);

        // Outside 180d tier
        WhatsAppClick::factory()->create(['created_at' => now()->subDays(181)]);
        // Inside 180d tier
        WhatsAppClick::factory()->create(['created_at' => now()->subDays(5)]);

        // Outside 365d tier
        $user = User::factory()->create();
        ActivityLog::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(366)]);
        // Inside 365d tier
        ActivityLog::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(100)]);

        (new RetentionCleanupJob)->handle();

        $this->assertSame(1, AnalyticsPageView::count(), 'Only recent page view survives.');
        $this->assertSame(1, WhatsAppClick::count(), 'Only recent click survives.');
        $this->assertSame(1, ActivityLog::count(), 'Only recent log survives.');
    }

    public function test_retention_tiers_read_from_setting(): void
    {
        Setting::set('retention', [
            'analytics_page_views' => 30,
            'whatsapp_clicks' => 60,
            'activity_logs' => 90,
        ]);

        AnalyticsPageView::factory()->create(['created_at' => now()->subDays(31)]);
        AnalyticsPageView::factory()->create(['created_at' => now()->subDays(2)]);

        (new RetentionCleanupJob)->handle();

        $this->assertSame(1, AnalyticsPageView::count(), 'Custom 30d tier must apply.');
    }

    public function test_expire_subscriptions_marks_expired_inactive_and_purges_old(): void
    {
        $user = User::factory()->create();

        $expired = PushSubscription::factory()->expired()->create([
            'user_id' => $user->id,
            'expires_at' => now()->subDays(1),
        ]);

        $fresh = PushSubscription::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addDays(30),
        ]);

        $inactiveOld = PushSubscription::factory()->inactive()->create([
            'user_id' => $user->id,
            'updated_at' => now()->subDays(40),
        ]);

        (new ExpirePushSubscriptionsJob)->handle();

        $this->assertFalse($expired->fresh()->active, 'Expired sub must be marked inactive.');
        $this->assertTrue($fresh->fresh()->active, 'Valid sub must stay active.');

        $purged = PushSubscription::find($inactiveOld->id);
        $this->assertNull($purged, 'Inactive >30d subscription must be purged.');
    }
}
