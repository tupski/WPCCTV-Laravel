<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsDaily;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\WhatsAppClick;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Read-only analytics JSON. All reads hit `analytics_daily` aggregates;
 * today (not yet aggregated) is computed live with bounded queries and
 * flagged `partial`.
 */
class AnalyticsController extends Controller
{
    public const CACHE_TTL_SECONDS = 300;

    public const DEFAULT_RANGE_DAYS = 30;

    public function overview(Request $request): JsonResponse
    {
        [$from, $to] = $this->resolveRange($request);

        $payload = Cache::remember(
            "analytics:overview:{$from->toDateString()}:{$to->toDateString()}",
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->buildOverview($from, $to),
        );

        return response()->json($payload);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildOverview(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $today = CarbonImmutable::today();
        $includesToday = $to->greaterThanOrEqualTo($today);

        $aggregateEnd = $includesToday ? $today->subDay() : $to;

        $rows = $aggregateEnd->lessThan($from)
            ? collect()
            : AnalyticsDaily::query()
                ->whereDate('date', '>=', $from->toDateString())
                ->whereDate('date', '<=', $aggregateEnd->toDateString())
                ->orderBy('date')
                ->get();

        $totals = [
            'page_views' => (int) $rows->sum('page_views'),
            'unique_sessions' => (int) $rows->sum('unique_sessions'),
            'whatsapp_clicks' => (int) $rows->sum('whatsapp_clicks'),
            'leads_created' => (int) $rows->sum('leads_created'),
            'leads_qualified' => (int) $rows->sum('leads_qualified'),
            'leads_won' => (int) $rows->sum('leads_won'),
        ];

        $series = $rows->map(fn (AnalyticsDaily $row): array => [
            'date' => $row->date?->toDateString(),
            'page_views' => (int) $row->page_views,
            'unique_sessions' => (int) $row->unique_sessions,
            'whatsapp_clicks' => (int) $row->whatsapp_clicks,
            'leads_created' => (int) $row->leads_created,
            'leads_qualified' => (int) $row->leads_qualified,
            'leads_won' => (int) $row->leads_won,
        ])->values()->all();

        if ($includesToday) {
            $todayStats = $this->todayStats();

            foreach ($totals as $key => $value) {
                $totals[$key] = $value + $todayStats[$key];
            }

            $series[] = ['date' => $today->toDateString()] + $todayStats;
        }

        return [
            'data' => [
                'range' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
                'totals' => $totals,
                'ratios' => [
                    'clicks_to_leads' => $this->ratio($totals['leads_created'], $totals['whatsapp_clicks']),
                    'leads_to_qualified' => $this->ratio($totals['leads_qualified'], $totals['leads_created']),
                    'qualified_to_won' => $this->ratio($totals['leads_won'], $totals['leads_qualified']),
                ],
                'daily' => $series,
                'partial' => $includesToday,
            ],
        ];
    }

    /**
     * Live, bounded counts for today (not yet in analytics_daily).
     *
     * @return array<string, int>
     */
    protected function todayStats(): array
    {
        $today = CarbonImmutable::today()->toDateString();

        return [
            'page_views' => \App\Models\AnalyticsPageView::query()->whereDate('created_at', $today)->count(),
            'unique_sessions' => \App\Models\AnalyticsPageView::query()
                ->whereDate('created_at', $today)
                ->distinct()
                ->count('session_id'),
            'whatsapp_clicks' => WhatsAppClick::query()->whereDate('created_at', $today)->count(),
            'leads_created' => Lead::query()->whereDate('created_at', $today)->count(),
            'leads_qualified' => $this->statusTransitionsToday($today, Lead::STATUS_QUALIFIED),
            'leads_won' => $this->statusTransitionsToday($today, Lead::STATUS_WON),
        ];
    }

    protected function statusTransitionsToday(string $date, string $status): int
    {
        return LeadActivity::query()
            ->where('action', LeadActivity::ACTION_STATUS_CHANGE)
            ->where('new_value', $status)
            ->whereDate('created_at', $date)
            ->count();
    }

    public function whatsappClicks(Request $request): JsonResponse
    {
        [$from, $to] = $this->resolveRange($request);

        $query = WhatsAppClick::query()
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString());

        if ($pageType = $request->query('page_type')) {
            $query->where('page_type', (string) $pageType);
        }

        $clicks = $query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(25)
            ->appends($request->query());

        $byPageType = (clone $query)
            ->select('page_type', DB::raw('COUNT(*) as total'))
            ->groupBy('page_type')
            ->orderByDesc('total')
            ->pluck('total', 'page_type')
            ->all();

        $byDevice = (clone $query)
            ->select('device_category', DB::raw('COUNT(*) as total'))
            ->groupBy('device_category')
            ->orderByDesc('total')
            ->pluck('total', 'device_category')
            ->all();

        return response()->json([
            'data' => $clicks->items(),
            'meta' => $clicks->toArray()['meta'] ?? null,
            'breakdown' => [
                'by_page_type' => $byPageType,
                'by_device_category' => $byDevice,
            ],
            'partial' => $to->greaterThanOrEqualTo(CarbonImmutable::today()),
        ]);
    }

    public function leads(Request $request): JsonResponse
    {
        [$from, $to] = $this->resolveRange($request);

        $funnel = Lead::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $statuses = [];

        foreach (Lead::STATUSES as $status) {
            $statuses[$status] = (int) ($funnel[$status] ?? 0);
        }

        $aggregatedEnd = $to->greaterThanOrEqualTo(CarbonImmutable::today())
            ? CarbonImmutable::today()->subDay()
            : $to;

        $aggregateQualified = $aggregatedEnd->lessThan($from)
            ? 0
            : (int) AnalyticsDaily::query()
                ->whereDate('date', '>=', $from->toDateString())
                ->whereDate('date', '<=', $aggregatedEnd->toDateString())
                ->sum('leads_qualified');

        $aggregateWon = $aggregatedEnd->lessThan($from)
            ? 0
            : (int) AnalyticsDaily::query()
                ->whereDate('date', '>=', $from->toDateString())
                ->whereDate('date', '<=', $aggregatedEnd->toDateString())
                ->sum('leads_won');

        return response()->json([
            'data' => [
                'range' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
                'by_status' => $statuses,
                'funnel' => [
                    'created' => array_sum($statuses),
                    'qualified' => $aggregateQualified,
                    'won' => $aggregateWon,
                ],
            ],
            'partial' => $to->greaterThanOrEqualTo(CarbonImmutable::today()),
        ]);
    }

    /**
     * Guarded division: null on a zero (or missing) denominator.
     *
     * Both guards are load-bearing and are checked in the order 0 → divide →
     * finite, not divide → check. A denominator is an `int` today, so PHP
     * would throw `DivisionByZeroError` rather than produce `INF`; the
     * `is_finite` check exists because the numerator/denominator types are the
     * only thing preventing an `INF` here, and the surrounding code already
     * casts from database values (audit M4). Returning null means the API
     * emits `null` and the view renders the missing marker instead of a
     * plausible-looking wrong number.
     */
    protected function ratio(int $numerator, int $denominator): ?float
    {
        if ($denominator === 0) {
            return null;
        }

        $value = $numerator / $denominator;

        return is_finite($value) ? round($value, 4) : null;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function resolveRange(Request $request): array
    {
        $to = $this->parseDate($request->query('to')) ?? CarbonImmutable::today();
        $from = $this->parseDate($request->query('from')) ?? $to->subDays(self::DEFAULT_RANGE_DAYS - 1);

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->startOfDay()];
    }

    protected function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date->startOfDay();
    }
}
