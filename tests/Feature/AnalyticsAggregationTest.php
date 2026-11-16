<?php

namespace Tests\Feature;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsPageView;
use App\Models\BlogArticle;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Package;
use App\Models\Product;
use App\Models\WhatsAppClick;
use App\Services\AnalyticsAggregationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsAggregationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function service(): AnalyticsAggregationService
    {
        return app(AnalyticsAggregationService::class);
    }

    public function test_aggregate_day_counts_raw_rows(): void
    {
        $day = CarbonImmutable::parse('2026-03-10 12:00:00');
        CarbonImmutable::setTestNow($day);

        AnalyticsPageView::factory()->count(5)->create([
            'session_id' => 's-1',
            'created_at' => $day->setTime(9, 0),
        ]);
        AnalyticsPageView::factory()->count(3)->create([
            'session_id' => 's-2',
            'created_at' => $day->setTime(10, 0),
        ]);

        WhatsAppClick::factory()->count(4)->create(['created_at' => $day->setTime(11, 0)]);
        Lead::factory()->count(2)->create(['created_at' => $day->setTime(8, 0)]);

        $lead = Lead::factory()->create();
        LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'action' => LeadActivity::ACTION_STATUS_CHANGE,
            'field' => 'status',
            'old_value' => 'new',
            'new_value' => Lead::STATUS_QUALIFIED,
            'created_at' => $day->setTime(13, 0),
        ]);
        LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'action' => LeadActivity::ACTION_STATUS_CHANGE,
            'field' => 'status',
            'old_value' => 'qualified',
            'new_value' => Lead::STATUS_WON,
            'created_at' => $day->setTime(14, 0),
        ]);

        $row = $this->service()->aggregateDay($day);

        $this->assertSame(8, $row->page_views);
        $this->assertSame(2, $row->unique_sessions);
        $this->assertSame(4, $row->whatsapp_clicks);
        $this->assertSame(3, $row->leads_created);
        $this->assertSame(1, $row->leads_qualified);
        $this->assertSame(1, $row->leads_won);
        $this->assertSame('2026-03-10', $row->date->toDateString());
    }

    public function test_aggregate_day_ignores_other_days(): void
    {
        $day = CarbonImmutable::parse('2026-03-10 12:00:00');
        CarbonImmutable::setTestNow($day);

        AnalyticsPageView::factory()->create(['created_at' => $day->setTime(9, 0)]);
        AnalyticsPageView::factory()->count(7)->create([
            'created_at' => $day->subDay()->setTime(9, 0),
        ]);

        $row = $this->service()->aggregateDay($day);

        $this->assertSame(1, $row->page_views);
    }

    public function test_aggregate_day_is_idempotent(): void
    {
        $day = CarbonImmutable::parse('2026-03-10 12:00:00');
        CarbonImmutable::setTestNow($day);

        AnalyticsPageView::factory()->count(3)->create(['created_at' => $day->setTime(9, 0)]);
        WhatsAppClick::factory()->count(2)->create(['created_at' => $day->setTime(10, 0)]);

        $first = $this->service()->aggregateDay($day);
        $second = $this->service()->aggregateDay($day);

        $this->assertSame(1, AnalyticsDaily::count());
        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->page_views, $second->page_views);
        $this->assertSame($first->whatsapp_clicks, $second->whatsapp_clicks);
        $this->assertSame($first->unique_sessions, $second->unique_sessions);
    }

    public function test_top_product_package_and_article_are_resolved(): void
    {
        $day = CarbonImmutable::parse('2026-03-10 12:00:00');
        CarbonImmutable::setTestNow($day);

        $product = Product::factory()->create();
        $package = Package::factory()->create();
        $article = BlogArticle::factory()->create();

        WhatsAppClick::factory()->count(2)->create([
            'page_type' => 'product',
            'context_id' => $product->id,
            'created_at' => $day->setTime(9, 0),
        ]);
        WhatsAppClick::factory()->create([
            'page_type' => 'product',
            'context_id' => Product::factory()->create()->id,
            'created_at' => $day->setTime(10, 0),
        ]);

        WhatsAppClick::factory()->create([
            'page_type' => 'package',
            'context_id' => $package->id,
            'created_at' => $day->setTime(11, 0),
        ]);

        WhatsAppClick::factory()->create([
            'page_type' => 'article',
            'context_id' => $article->id,
            'created_at' => $day->setTime(12, 0),
        ]);

        $row = $this->service()->aggregateDay($day);

        $this->assertSame($product->id, $row->top_product_id);
        $this->assertSame($package->id, $row->top_package_id);
        $this->assertSame($article->id, $row->top_article_id);
    }

    public function test_top_entities_are_null_when_no_clicks(): void
    {
        $day = CarbonImmutable::parse('2026-03-10 12:00:00');
        CarbonImmutable::setTestNow($day);

        $row = $this->service()->aggregateDay($day);

        $this->assertNull($row->top_product_id);
        $this->assertNull($row->top_package_id);
        $this->assertNull($row->top_article_id);
        $this->assertSame(0, $row->page_views);
    }

    public function test_aggregate_range_processes_each_day(): void
    {
        $end = CarbonImmutable::parse('2026-03-10 12:00:00');
        CarbonImmutable::setTestNow($end);

        $from = $end->subDays(2);

        AnalyticsPageView::factory()->create(['created_at' => $from->setTime(9, 0)]);
        AnalyticsPageView::factory()->create(['created_at' => $from->addDay()->setTime(9, 0)]);
        AnalyticsPageView::factory()->count(2)->create(['created_at' => $end->setTime(9, 0)]);

        $count = $this->service()->aggregateRange($from, $end);

        $this->assertSame(3, $count);
        $this->assertSame(3, AnalyticsDaily::count());
        $this->assertSame(1, (int) AnalyticsDaily::whereDate('date', $from->toDateString())->value('page_views'));
        $this->assertSame(2, (int) AnalyticsDaily::whereDate('date', $end->toDateString())->value('page_views'));
    }

    public function test_aggregate_range_returns_zero_for_inverted_range(): void
    {
        $to = CarbonImmutable::parse('2026-03-10');
        $from = CarbonImmutable::parse('2026-03-12');

        $this->assertSame(0, $this->service()->aggregateRange($from, $to));
        $this->assertSame(0, AnalyticsDaily::count());
    }

    public function test_qualified_and_won_count_only_status_change_activities(): void
    {
        $day = CarbonImmutable::parse('2026-03-10 12:00:00');
        CarbonImmutable::setTestNow($day);

        $lead = Lead::factory()->create();

        LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'action' => LeadActivity::ACTION_NOTE,
            'new_value' => Lead::STATUS_QUALIFIED,
            'created_at' => $day->setTime(9, 0),
        ]);

        LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'action' => LeadActivity::ACTION_STATUS_CHANGE,
            'new_value' => Lead::STATUS_QUALIFIED,
            'created_at' => $day->setTime(10, 0),
        ]);

        LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'action' => LeadActivity::ACTION_STATUS_CHANGE,
            'new_value' => Lead::STATUS_WON,
            'created_at' => $day->setTime(11, 0),
        ]);

        $row = $this->service()->aggregateDay($day);

        $this->assertSame(1, $row->leads_qualified);
        $this->assertSame(1, $row->leads_won);
    }

    public function test_command_aggregates_yesterday_by_default(): void
    {
        $today = CarbonImmutable::parse('2026-03-11 01:00:00');
        CarbonImmutable::setTestNow($today);

        AnalyticsPageView::factory()->count(2)->create([
            'created_at' => $today->subDay()->setTime(9, 0),
        ]);

        $this->artisan('analytics:aggregate')->assertSuccessful();

        $this->assertSame(1, AnalyticsDaily::count());
        $this->assertSame('2026-03-10', AnalyticsDaily::first()->date->toDateString());
        $this->assertSame(2, AnalyticsDaily::first()->page_views);
    }

    public function test_command_backfills_with_days_option(): void
    {
        $today = CarbonImmutable::parse('2026-03-11 01:00:00');
        CarbonImmutable::setTestNow($today);

        $this->artisan('analytics:aggregate', ['--days' => 5])->assertSuccessful();

        $this->assertSame(5, AnalyticsDaily::count());
    }

    public function test_command_accepts_explicit_date(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-20 01:00:00'));

        $this->artisan('analytics:aggregate', ['--date' => '2026-03-05'])->assertSuccessful();

        $this->assertSame(1, AnalyticsDaily::count());
        $this->assertSame('2026-03-05', AnalyticsDaily::first()->date->toDateString());
    }

    public function test_command_rejects_invalid_date(): void
    {
        $this->artisan('analytics:aggregate', ['--date' => 'not-a-date'])->assertFailed();

        $this->assertSame(0, AnalyticsDaily::count());
    }
}
