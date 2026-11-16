<?php

namespace App\Services;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsPageView;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\WhatsAppClick;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Rolls raw analytics rows into the `analytics_daily` aggregate table.
 *
 * Bounded by design:
 * - one row per date, written via updateOrCreate (idempotent)
 * - every count is an indexed aggregate query on `created_at` / `page_type`
 * - the "top entity" lookups are single GROUP BY ... LIMIT 1 queries,
 *   never a loop over entities
 */
class AnalyticsAggregationService
{
    /**
     * Aggregate a single day and return the persisted row.
     *
     * Idempotent: re-running for the same date rewrites the same values.
     */
    public function aggregateDay(CarbonImmutable $date): AnalyticsDaily
    {
        $day = $date->startOfDay();

        $pageViews = AnalyticsPageView::query()
            ->whereDate('created_at', $day->toDateString())
            ->count();

        $uniqueSessions = AnalyticsPageView::query()
            ->whereDate('created_at', $day->toDateString())
            ->distinct()
            ->count('session_id');

        $whatsappClicks = $this->whatsappClicksOn($day);

        $leadsCreated = Lead::query()
            ->whereDate('created_at', $day->toDateString())
            ->count();

        $leadsQualified = $this->statusTransitionsOn($day, Lead::STATUS_QUALIFIED);
        $leadsWon = $this->statusTransitionsOn($day, Lead::STATUS_WON);

        // The `date` cast persists a full timestamp, so a plain where('date')
        // lookup would miss existing rows. whereDate keeps the write idempotent.
        $row = AnalyticsDaily::query()
            ->whereDate('date', $day->toDateString())
            ->first() ?? new AnalyticsDaily(['date' => $day->toDateString()]);

        $row->fill([
            'date' => $day->toDateString(),
            'page_views' => $pageViews,
            'unique_sessions' => $uniqueSessions,
            'whatsapp_clicks' => $whatsappClicks,
            'leads_created' => $leadsCreated,
            'leads_qualified' => $leadsQualified,
            'leads_won' => $leadsWon,
            'top_product_id' => $this->topContextId($day, 'product'),
            'top_package_id' => $this->topContextId($day, 'package'),
            'top_article_id' => $this->topContextId($day, 'article'),
        ]);

        $row->save();

        return $row;
    }

    /**
     * Aggregate an inclusive date range, day by day.
     *
     * Returns the number of days processed. `$to` past `$from` yields 0.
     */
    public function aggregateRange(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $cursor = $from->startOfDay();
        $end = $to->startOfDay();

        $processed = 0;

        while ($cursor->lessThanOrEqualTo($end)) {
            $this->aggregateDay($cursor);
            $processed++;
            $cursor = $cursor->addDay();
        }

        return $processed;
    }

    protected function whatsappClicksOn(CarbonImmutable $day): int
    {
        return WhatsAppClick::query()
            ->whereDate('created_at', $day->toDateString())
            ->count();
    }

    /**
     * Count status_change activities that landed on `$status` that day.
     */
    protected function statusTransitionsOn(CarbonImmutable $day, string $status): int
    {
        return LeadActivity::query()
            ->where('action', LeadActivity::ACTION_STATUS_CHANGE)
            ->where('new_value', $status)
            ->whereDate('created_at', $day->toDateString())
            ->count();
    }

    /**
     * Most-clicked context_id for a page type on a given day, or null.
     *
     * Single bounded query: GROUP BY context_id ORDER BY clicks DESC LIMIT 1.
     */
    protected function topContextId(CarbonImmutable $day, string $pageType): ?int
    {
        $row = WhatsAppClick::query()
            ->select('context_id', DB::raw('COUNT(*) as clicks'))
            ->where('page_type', $pageType)
            ->whereDate('created_at', $day->toDateString())
            ->whereNotNull('context_id')
            ->groupBy('context_id')
            ->orderByDesc('clicks')
            ->orderBy('context_id')
            ->first();

        return $row?->context_id === null ? null : (int) $row->context_id;
    }
}
