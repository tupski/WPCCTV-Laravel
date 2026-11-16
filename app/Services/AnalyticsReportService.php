<?php

namespace App\Services;

use App\Models\AnalyticsDaily;
use App\Models\WhatsAppClick;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Read model for the admin reporting screens.
 *
 * Every number comes from `analytics_daily`; the current day is rolled up
 * first through AnalyticsAggregationService so a dashboard opened at 9am is
 * not missing today. Results are cached for five minutes, which is also why
 * today's figure is described to the reader as partial.
 */
class AnalyticsReportService
{
    public const CACHE_TTL_SECONDS = 300;

    public function __construct(protected AnalyticsAggregationService $aggregation) {}

    /**
     * Totals, daily series and funnel for an inclusive date range.
     *
     * @return array{
     *     from: CarbonImmutable,
     *     to: CarbonImmutable,
     *     includes_today: bool,
     *     totals: array<string, int>,
     *     daily: array<int, array<string, mixed>>,
     *     funnel: array<int, array{key: string, label: string, count: int, denominator: int|null, ratio: float|null}>
     * }
     */
    public function forRange(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $from = $from->startOfDay();
        $to = $to->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $today = CarbonImmutable::today();

        $cacheKey = 'admin-report:'.$from->toDateString().':'.$to->toDateString();

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($from, $to, $today): array {
            // Roll up today inside the cache closure: the aggregate write is
            // the expensive part, so it must not run on every request while
            // the cached figure is still fresh.
            if ($to->greaterThanOrEqualTo($today) && $from->lessThanOrEqualTo($today)) {
                $this->aggregation->aggregateDay($today);
            }

            $rows = AnalyticsDaily::query()
                ->whereDate('date', '>=', $from->toDateString())
                ->whereDate('date', '<=', $to->toDateString())
                ->orderBy('date')
                ->get();

            $daily = $rows->map(fn (AnalyticsDaily $row): array => [
                'date' => $row->date?->toDateString(),
                'page_views' => (int) $row->page_views,
                'unique_sessions' => (int) $row->unique_sessions,
                'whatsapp_clicks' => (int) $row->whatsapp_clicks,
                'leads_created' => (int) $row->leads_created,
                'leads_qualified' => (int) $row->leads_qualified,
                'leads_won' => (int) $row->leads_won,
            ])->values()->all();

            $totals = [
                'page_views' => (int) $rows->sum('page_views'),
                'unique_sessions' => (int) $rows->sum('unique_sessions'),
                'whatsapp_clicks' => (int) $rows->sum('whatsapp_clicks'),
                'leads_created' => (int) $rows->sum('leads_created'),
                'leads_qualified' => (int) $rows->sum('leads_qualified'),
                'leads_won' => (int) $rows->sum('leads_won'),
            ];

            return [
                'from' => $from,
                'to' => $to,
                'includes_today' => $to->greaterThanOrEqualTo($today) && $from->lessThanOrEqualTo($today),
                'totals' => $totals,
                'daily' => $daily,
                'funnel' => $this->funnel($totals),
            ];
        });
    }

    /**
     * WhatsApp clicks split by the page they came from and by device.
     *
     * @return array{
     *     by_page_type: array<int, array{key: string, count: int}>,
     *     by_device: array<int, array{key: string, count: int}>,
     *     total: int
     * }
     */
    public function clickBreakdown(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $from = $from->startOfDay();
        $to = $to->startOfDay();

        $byPageType = $this->groupedClicks('page_type', $from, $to);
        $byDevice = $this->groupedClicks('device_category', $from, $to);

        return [
            'by_page_type' => $byPageType,
            'by_device' => $byDevice,
            'total' => array_sum(array_column($byPageType, 'count')),
        ];
    }

    /**
     * Funnel steps: clicks → leads → qualified → won.
     *
     * `denominator` is null for the first step (nothing precedes it), and
     * `ratio` is null whenever its denominator is zero so the view can print
     * the missing marker instead of a misleading 0%.
     *
     * @param  array<string, int>  $totals
     * @return array<int, array{key: string, label: string, count: int, denominator: int|null, ratio: float|null}>
     */
    protected function funnel(array $totals): array
    {
        $steps = [
            ['key' => 'whatsapp_clicks', 'label' => 'Klik WhatsApp', 'count' => $totals['whatsapp_clicks'], 'denominator' => null],
            ['key' => 'leads_created', 'label' => 'Lead dibuat', 'count' => $totals['leads_created'], 'denominator' => $totals['whatsapp_clicks']],
            ['key' => 'leads_qualified', 'label' => 'Lead berkualifikasi', 'count' => $totals['leads_qualified'], 'denominator' => $totals['leads_created']],
            ['key' => 'leads_won', 'label' => 'Lead deal', 'count' => $totals['leads_won'], 'denominator' => $totals['leads_qualified']],
        ];

        return array_map(function (array $step): array {
            $step['ratio'] = $this->ratio($step['count'], $step['denominator']);

            return $step;
        }, $steps);
    }

    /**
     * A ratio, or null when it has no valid meaning.
     *
     * The one guard that matters here is the null/zero denominator, because a
     * denominator is an `int` and integer division by zero throws
     * `DivisionByZeroError` rather than returning `INF`. What made `INF`
     * reachable was the ordering, not the modality: the old code computed
     * `$step['count'] / $denominator` inside the array_map and relied on a
     * guard written on the previous line. Any future caller that adds a step,
     * or a float denominator, bypasses it. The division therefore lives in
     * this single guarded function, and the result is validated before it
     * leaves — so `ratio()` cannot return a non-finite value no matter what
     * the inputs become (audit M4).
     */
    protected function ratio(int $numerator, int|null $denominator): ?float
    {
        if ($denominator === null || $denominator === 0) {
            return null;
        }

        $value = $numerator / $denominator;

        return is_finite($value) ? $value : null;
    }

    /**
     * @return array<int, array{key: string, count: int}>
     */
    protected function groupedClicks(string $column, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return WhatsAppClick::query()
            ->select($column, DB::raw('COUNT(*) as total'))
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->groupBy($column)
            ->orderByDesc('total')
            ->orderBy($column)
            ->get()
            ->map(fn ($row): array => [
                'key' => (string) ($row->{$column} ?? ''),
                'count' => (int) $row->total,
            ])
            ->all();
    }
}
