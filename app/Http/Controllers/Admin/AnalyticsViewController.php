<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppClick;
use App\Services\AnalyticsReportService;
use App\Support\Format;
use App\Support\Labels;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Reporting screens. Reads `analytics_daily` aggregates through
 * AnalyticsReportService; today is rolled up on read and flagged partial.
 */
class AnalyticsViewController extends Controller
{
    public const RANGES = [7, 30, 90];

    public const DEFAULT_RANGE = 30;

    public function __construct(protected AnalyticsReportService $reports) {}

    public function index(Request $request)
    {
        Gate::authorize('view-analytics');

        $days = $this->resolveDays($request);
        $to = CarbonImmutable::today();
        $from = $to->subDays($days - 1);

        $report = $this->reports->forRange($from, $to);

        return view('admin.analytics.index', [
            'rangeDays' => $days,
            'rangeLinks' => $this->rangeLinks($request, $days),
            'rangeLabel' => Format::date($from).' – '.Format::date($to),
            'totals' => $this->totals($report['totals']),
            'bars' => $this->bars($report['daily'], $this->maxDaily($report['daily'])),
            'funnel' => $report['funnel'],
            'includesToday' => $report['includes_today'],
            'hasData' => $report['totals']['page_views'] > 0 || $report['totals']['whatsapp_clicks'] > 0,
        ]);
    }

    public function whatsappClicks(Request $request)
    {
        Gate::authorize('view-analytics');

        $days = $this->resolveDays($request);
        $to = CarbonImmutable::today();
        $from = $to->subDays($days - 1);

        $breakdown = $this->reports->clickBreakdown($from, $to);

        $clicks = WhatsAppClick::query()
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->appends($request->query());

        return view('admin.analytics.whatsapp-clicks', [
            'rangeDays' => $days,
            'rangeLinks' => $this->rangeLinks($request, $days),
            'rangeLabel' => Format::date($from).' – '.Format::date($to),
            'byPageType' => $this->labelRows($breakdown['by_page_type'], Labels::PAGE_TYPE, 'Halaman tidak diketahui'),
            'byDevice' => $this->labelRows($breakdown['by_device'], Labels::DEVICE, 'Tidak diketahui'),
            'total' => $breakdown['total'],
            'clicks' => $clicks,
            'includesToday' => true,
        ]);
    }

    /**
     * Every total with the denominator that produced it, so no percentage
     * stands alone.
     *
     * @param  array<string, int>  $totals
     * @return array<int, array<string, mixed>>
     */
    protected function totals(array $totals): array
    {
        return [
            [
                'label' => 'Kunjungan halaman',
                'value' => $totals['page_views'],
                'note' => 'Seluruh tampilan halaman publik pada rentang ini.',
            ],
            [
                'label' => 'Sesi unik',
                'value' => $totals['unique_sessions'],
                // Audit M2: this counts distinct `analytics_page_views.session_id`,
                // which is the visitor cookie when present and the session id
                // otherwise. Click-level `visitor_token` is NOT the source,
                // because a visitor's first click always stores NULL (the
                // cookie is minted on that same response) and would be dropped
                // by COUNT(DISTINCT). The number is therefore a per-session
                // estimate, not a count of people: one browser with cookies
                // cleared reads as two. Labelled, never implied.
                'note' => 'Dari '.Format::number($totals['page_views']).' kunjungan halaman. Sesi unik adalah perkiraan, bukan jumlah orang.',
            ],
            [
                'label' => 'Klik WhatsApp',
                'value' => $totals['whatsapp_clicks'],
                'note' => 'Dari '.Format::number($totals['unique_sessions']).' sesi unik.',
            ],
            [
                'label' => 'Lead dibuat',
                'value' => $totals['leads_created'],
                'note' => 'Dari '.Format::number($totals['whatsapp_clicks']).' klik WhatsApp.',
            ],
            [
                'label' => 'Lead deal',
                'value' => $totals['leads_won'],
                'note' => 'Dari '.Format::number($totals['leads_created']).' lead yang dibuat.',
            ],
        ];
    }

    protected function maxDaily(array $daily): int
    {
        return max(1, ...array_map(fn (array $row): int => (int) $row['whatsapp_clicks'], $daily ?: [['whatsapp_clicks' => 0]]));
    }

    /**
     * Horizontal bar rows. Width is a percentage of the largest day in the
     * range; the table cell carries the real number, the bar is only a shape.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function bars(array $daily, int $max): array
    {
        return array_map(fn (array $row): array => [
            'date' => Format::date(CarbonImmutable::parse($row['date'])),
            'value' => (int) $row['whatsapp_clicks'],
            'leads' => (int) $row['leads_created'],
            'width' => $row['whatsapp_clicks'] === 0
                ? 0
                : max(2, (int) round($row['whatsapp_clicks'] / $max * 100)),
        ], $daily);
    }

    /**
     * @param  array<int, array{key: string, count: int}>  $rows
     * @param  array<string, string>  $labels
     * @return array<int, array{label: string, count: int, share: string}>
     */
    protected function labelRows(array $rows, array $labels, string $fallback): array
    {
        $total = array_sum(array_column($rows, 'count'));

        return array_map(fn (array $row): array => [
            'label' => $row['key'] === '' ? $fallback : ($labels[$row['key']] ?? $row['key']),
            'count' => $row['count'],
            'share' => Format::percent($total === 0 ? null : $row['count'] / $total, 0),
        ], $rows);
    }

    /**
     * Range links keep every other query parameter intact.
     *
     * @return array<int, array{days: int, label: string, url: string, current: bool}>
     */
    protected function rangeLinks(Request $request, int $current): array
    {
        $query = $request->query();
        unset($query['page'], $query['days']);

        return array_map(fn (int $days): array => [
            'days' => $days,
            'label' => $days.' hari',
            'url' => $request->url().'?'.http_build_query(['days' => $days] + $query),
            'current' => $days === $current,
        ], self::RANGES);
    }

    protected function resolveDays(Request $request): int
    {
        $days = (int) $request->query('days', (string) self::DEFAULT_RANGE);

        return in_array($days, self::RANGES, true) ? $days : self::DEFAULT_RANGE;
    }
}
