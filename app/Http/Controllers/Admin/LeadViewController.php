<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignLeadRequest;
use App\Http\Requests\Admin\ConvertWhatsAppClickRequest;
use App\Http\Requests\Admin\ScheduleLeadFollowUpRequest;
use App\Http\Requests\Admin\StoreLeadNoteRequest;
use App\Http\Requests\Admin\UpdateLeadStatusRequest;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Models\WhatsAppClick;
use App\Policies\LeadPolicy;
use App\Services\LeadConversionService;
use App\Support\Format;
use App\Support\Labels;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Server-rendered lead workspace.
 *
 * Reads and writes go through the lead policy, so a `lead_manager` only ever
 * sees and touches leads assigned to them. Status transitions are delegated
 * to LeadConversionService — never a direct `update()` — so the audit trail
 * stays complete.
 */
class LeadViewController extends Controller
{
    public function __construct(
        protected LeadConversionService $leads,
        protected LeadPolicy $policy,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Lead::class);

        $user = $request->user();
        $filters = [
            'status' => $this->queryString($request, 'status'),
            'assigned_to' => $this->queryString($request, 'assigned_to'),
            'lead_type' => $this->queryString($request, 'lead_type'),
            'q' => $this->queryString($request, 'q'),
        ];

        $base = $this->scopedFor($user);

        return view('admin.leads.index', [
            'leads' => $this->filtered(clone $base, $filters)
                ->with('assignedTo:id,name')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(25)
                ->appends($request->query()),
            'filters' => $filters,
            'statusOptions' => $this->statusOptions($base, $filters),
            'assignees' => $this->assignableUsers(),
            'leadTypes' => Labels::LEAD_TYPE,
            'hasFilters' => array_filter($filters) !== [],
            'canSeeAllLeads' => $this->policy->seesAllLeads($user),
        ]);
    }

    /**
     * Same scoping rule the policy applies to a single lead, expressed as a
     * query so the list cannot show a row the detail page would refuse.
     *
     * @return \\Illuminate\\Database\\Eloquent\\Builder<Lead>
     */
    protected function scopedFor(User $user)
    {
        $query = Lead::query();

        if (! $this->policy->seesAllLeads($user)) {
            $query->where('assigned_to', $user->id);
        }

        return $query;
    }

    public function show(Request $request, Lead $lead)
    {
        Gate::authorize('view', $lead);

        $lead->load(['whatsappClick', 'assignedTo:id,name']);

        $records = LeadActivity::query()
            ->where('lead_id', $lead->id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        // One query resolves every assignee name referenced by the timeline,
        // instead of one lookup per row.
        $names = User::query()
            ->whereIn('id', $this->referencedUserIds($records))
            ->pluck('name', 'id')
            ->all();

        $activities = $records
            ->map(fn (LeadActivity $activity): array => [
                'id' => $activity->id,
                'when' => Format::dateTime($activity->created_at),
                'relative' => Format::relative($activity->created_at),
                'who' => $activity->user?->name ?? 'Sistem',
                'action' => Labels::activity($activity->action),
                'field' => $this->fieldLabel($activity->field),
                'change' => $this->describeChange($activity, $names),
            ])
            ->all();

        return view('admin.leads.show', [
            'lead' => $lead,
            'activities' => $activities,
            'statusOptions' => Labels::STATUS,
            'assignees' => $this->assignableUsers(),
            'click' => $lead->whatsappClick,
            'followUp' => [
                'date' => Format::date($lead->follow_up_at),
                'deadline' => Format::deadline($lead->follow_up_at),
                'input' => $lead->follow_up_at?->format('Y-m-d'),
            ],
        ]);
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead): RedirectResponse
    {
        $newStatus = (string) $request->validated()['status'];

        if ($newStatus === $lead->status) {
            return back()->with('status', 'Status lead tidak berubah.');
        }

        $this->leads->changeStatus($lead, $newStatus, $request->user());

        return back()->with(
            'status',
            'Status lead diubah menjadi '.Labels::status($newStatus).'.',
        );
    }

    public function storeNote(StoreLeadNoteRequest $request, Lead $lead): RedirectResponse
    {
        $this->leads->addNote($lead, (string) $request->validated()['note'], $request->user());

        return back()->with('status', 'Catatan ditambahkan ke riwayat lead.');
    }

    public function assign(AssignLeadRequest $request, Lead $lead): RedirectResponse
    {
        $assigneeId = $request->validated()['assigned_to'] ?? null;
        $assignee = $assigneeId === null ? null : User::find($assigneeId);

        $this->leads->assign($lead, $assignee, $request->user());

        return back()->with(
            'status',
            $assignee === null
                ? 'Penugasan lead dikosongkan.'
                : 'Lead ditugaskan kepada '.$assignee->name.'.',
        );
    }

    public function scheduleFollowUp(ScheduleLeadFollowUpRequest $request, Lead $lead): RedirectResponse
    {
        $at = new \DateTimeImmutable((string) $request->validated()['follow_up_at']);

        $this->leads->scheduleFollowUp($lead, $at, $request->user());

        return back()->with('status', 'Tindak lanjut dijadwalkan pada '.Format::date($at).'.');
    }

    /**
     * Conversion form. A click that already produced a lead shows that lead
     * instead of a second conversion form.
     */
    public function convertForm(WhatsAppClick $whatsappClick)
    {
        Gate::authorize('create', Lead::class);

        if ($existing = $this->existingLeadFor($whatsappClick)) {
            return redirect()
                ->route('admin.leads-ui.show', $existing)
                ->with('status', 'Klik WhatsApp ini sudah dikonversi menjadi lead berikut.');
        }

        return view('admin.leads.convert', [
            'click' => $whatsappClick,
            'pageType' => Labels::pageType($whatsappClick->page_type),
            'device' => Labels::device($whatsappClick->device_category),
            'when' => Format::dateTime($whatsappClick->created_at),
            'leadTypes' => Labels::LEAD_TYPE,
        ]);
    }

    public function convert(ConvertWhatsAppClickRequest $request, WhatsAppClick $whatsappClick): RedirectResponse
    {
        if ($existing = $this->existingLeadFor($whatsappClick)) {
            return redirect()
                ->route('admin.leads-ui.show', $existing)
                ->with('status', 'Klik WhatsApp ini sudah dikonversi menjadi lead berikut.');
        }

        $lead = $this->leads->convert($whatsappClick, $request->validated(), $request->user());

        return redirect()
            ->route('admin.leads-ui.show', $lead)
            ->with('status', 'Lead baru dibuat dari klik WhatsApp.');
    }

    protected function existingLeadFor(WhatsAppClick $click): ?Lead
    {
        if ($click->converted_lead_id !== null) {
            $lead = Lead::find($click->converted_lead_id);

            if ($lead !== null) {
                return $lead;
            }
        }

        return Lead::where('whatsapp_click_id', $click->id)->first();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Lead>  $query
     * @param  array<string, string|null>  $filters
     * @return \Illuminate\Database\Eloquent\Builder<Lead>
     */
    protected function filtered($query, array $filters)
    {
        if (in_array($filters['status'], Lead::STATUSES, true)) {
            $query->where('status', $filters['status']);
        }

        if ($filters['assigned_to'] === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif ($filters['assigned_to'] !== null && ctype_digit($filters['assigned_to'])) {
            $query->where('assigned_to', (int) $filters['assigned_to']);
        }

        if (in_array($filters['lead_type'], Lead::TYPES, true)) {
            $query->where('lead_type', $filters['lead_type']);
        }

        if ($filters['q'] !== null) {
            $query->where(function ($inner) use ($filters): void {
                $inner->where('customer_name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('whatsapp_number', 'like', '%'.$filters['q'].'%');
            });
        }

        return $query;
    }

    /**
     * Segmented status counts. Each count honours every other active filter,
     * so the numbers describe the links they belong to.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Lead>  $query
     * @param  array<string, string|null>  $filters
     * @return array<int, array{key: string|null, label: string, count: int}>
     */
    protected function statusOptions($query, array $filters): array
    {
        $counts = $this->filtered(clone $query, ['status' => null] + $filters)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $options = [[
            'key' => null,
            'label' => 'Semua',
            'count' => (int) $counts->sum(),
        ]];

        foreach (Labels::STATUS as $key => $label) {
            $options[] = [
                'key' => $key,
                'label' => $label,
                'count' => (int) ($counts[$key] ?? 0),
            ];
        }

        return $options;
    }

    /**
     * Users who can own a lead.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function assignableUsers()
    {
        return User::query()
            ->whereIn('role', [
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMINISTRATOR,
                User::ROLE_LEAD_MANAGER,
            ])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function queryString(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    protected function fieldLabel(?string $field): ?string
    {
        return match ($field) {
            'status' => 'Status',
            'assigned_to' => 'Penanggung jawab',
            'follow_up_at' => 'Tindak lanjut',
            'identity' => 'Identitas',
            default => $field,
        };
    }

    /**
     * Every user id an assignment row mentions, old and new.
     *
     * @param  \Illuminate\Support\Collection<int, LeadActivity>  $activities
     * @return array<int, int>
     */
    protected function referencedUserIds($activities): array
    {
        $ids = [];

        foreach ($activities as $activity) {
            if ($activity->field !== 'assigned_to') {
                continue;
            }

            foreach ([$activity->old_value, $activity->new_value] as $value) {
                if ($value !== null && ctype_digit((string) $value)) {
                    $ids[] = (int) $value;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<int, string>  $names
     */
    protected function describeChange(LeadActivity $activity, array $names): ?string
    {
        if ($activity->action === LeadActivity::ACTION_NOTE) {
            return $activity->new_value;
        }

        if ($activity->field === 'status') {
            return Labels::status($activity->old_value).' → '.Labels::status($activity->new_value);
        }

        if ($activity->field === 'assigned_to') {
            return $this->userName($activity->old_value, $names).' → '.$this->userName($activity->new_value, $names);
        }

        if ($activity->field === 'follow_up_at') {
            return $this->dateOrDash($activity->old_value).' → '.$this->dateOrDash($activity->new_value);
        }

        return $activity->new_value;
    }

    /**
     * @param  array<int, string>  $names
     */
    protected function userName(?string $id, array $names): string
    {
        if ($id === null) {
            return 'Belum ditugaskan';
        }

        return $names[(int) $id] ?? 'Pengguna #'.$id;
    }

    protected function dateOrDash(?string $value): string
    {
        if ($value === null || $value === '') {
            return Format::MISSING;
        }

        try {
            return Format::date(CarbonImmutable::parse($value));
        } catch (\Throwable) {
            return $value;
        }
    }
}
