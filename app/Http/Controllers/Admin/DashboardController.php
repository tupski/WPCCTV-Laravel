<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\WhatsAppClick;
use App\Services\AnalyticsReportService;
use App\Support\Format;
use App\Support\Labels;
use Carbon\CarbonImmutable;

/**
 * Operational landing screen: what came in, what was won, what needs a call.
 *
 * Every figure is labelled and carries its time window, so a number can never
 * be read out of context.
 */
class DashboardController extends Controller
{
    public function __construct(protected AnalyticsReportService $reports) {}

    public function index()
    {
        $today = CarbonImmutable::today();
        $weekStart = $today->subDays(6);

        $todayTotals = $this->reports->forRange($today, $today)['totals'];
        $weekRange = $this->reports->forRange($weekStart, $today);
        $weekTotals = $weekRange['totals'];

        return view('admin.dashboard.index', [
            'metrics' => [
                [
                    'label' => 'Klik WhatsApp',
                    'today' => $todayTotals['whatsapp_clicks'],
                    'week' => $weekTotals['whatsapp_clicks'],
                ],
                [
                    'label' => 'Lead dibuat',
                    'today' => $todayTotals['leads_created'],
                    'week' => $weekTotals['leads_created'],
                ],
                [
                    'label' => 'Lead deal',
                    'today' => $todayTotals['leads_won'],
                    'week' => $weekTotals['leads_won'],
                ],
            ],
            'todayLabel' => Format::date($today),
            'weekLabel' => Format::date($weekStart).' – '.Format::date($today),
            'needsFollowUp' => $this->needsFollowUp(),
            'latestClicks' => $this->latestClicks(),
        ]);
    }

    /**
     * Leads whose follow-up date has arrived and are still open, soonest first.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function needsFollowUp(): array
    {
        return Lead::query()
            ->with('assignedTo:id,name')
            ->whereNotNull('follow_up_at')
            ->whereDate('follow_up_at', '<=', CarbonImmutable::today()->toDateString())
            ->whereNotIn('status', [Lead::STATUS_WON, Lead::STATUS_LOST, Lead::STATUS_ARCHIVED])
            ->orderBy('follow_up_at')
            ->orderBy('id')
            ->limit(8)
            ->get()
            ->map(fn (Lead $lead): array => [
                'id' => $lead->id,
                'customer' => $lead->customer_name ?: 'Tanpa nama pelanggan',
                'phone' => $lead->whatsapp_number,
                'status' => Labels::status($lead->status),
                'status_tone' => Labels::statusTone($lead->status),
                'type' => Labels::leadType($lead->lead_type),
                'assignee' => $lead->assignedTo?->name,
                'deadline' => Format::deadline($lead->follow_up_at),
                'deadline_at' => Format::date($lead->follow_up_at),
            ])
            ->all();
    }

    /**
     * Last five WhatsApp clicks with the page they came from.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function latestClicks(): array
    {
        return WhatsAppClick::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (WhatsAppClick $click): array => [
                'id' => $click->id,
                'title' => $click->page_title ?: $click->page_url,
                'page_type' => Labels::pageType($click->page_type),
                'device' => Labels::device($click->device_category),
                'when' => Format::dateTime($click->created_at),
                'relative' => Format::relative($click->created_at),
                'converted' => $click->converted_lead_id !== null,
            ])
            ->all();
    }
}
