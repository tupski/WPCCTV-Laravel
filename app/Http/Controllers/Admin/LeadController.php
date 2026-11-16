<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeadRequest;
use App\Http\Requests\Admin\UpdateLeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsAppClick;
use App\Services\ActivityLogService;
use App\Services\LeadConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function __construct(
        protected LeadConversionService $leads,
        protected ActivityLogService $activityLog,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Lead::class);

        $query = Lead::with([
            'whatsappClick:id,page_type,page_url,event_id',
            'assignedTo:id,name,email',
            'activities' => fn ($q) => $q->orderByDesc('created_at')->orderByDesc('id')->limit(5),
        ])->latest();

        // lead_manager only sees leads assigned to them.
        $user = $request->user();

        if (! $user->hasRole(User::ROLE_SUPER_ADMIN, User::ROLE_ADMINISTRATOR)) {
            $query->where('assigned_to', $user->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($assignedTo = $request->query('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($leadType = $request->query('lead_type')) {
            $query->where('lead_type', $leadType);
        }

        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('customer_name', 'like', '%'.$search.'%')
                    ->orWhere('whatsapp_number', 'like', '%'.$search.'%');
            });
        }

        $leads = $query->paginate(25)->appends($request->query());

        return response()->json(['data' => $leads, 'meta' => $leads->toArray()['meta'] ?? null]);
    }

    public function show(Request $request, Lead $lead)
    {
        Gate::authorize('view', $lead);

        $lead->load([
            'whatsappClick',
            'assignedTo:id,name,email',
            'activities' => fn ($q) => $q->orderByDesc('created_at')->orderByDesc('id'),
        ]);

        return response()->json(['data' => $lead]);
    }

    /**
     * Manual lead creation (no WhatsApp click). Assignment and status are
     * server-forced.
     */
    public function store(StoreLeadRequest $request)
    {
        $data = $request->safe()->only([
            'customer_name', 'whatsapp_number', 'lead_type',
            'requirements', 'priority', 'notes', 'follow_up_at',
        ]);

        $data['status'] = Lead::STATUS_NEW;
        $data['assigned_to'] = $request->user()->id;

        $lead = Lead::create($data);

        $this->activityLog->log('lead.created', $lead, [
            'status' => $lead->status,
            'lead_type' => $lead->lead_type,
        ], $request->user());

        return response()->json(['data' => $lead->fresh()], 201);
    }

    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        Gate::authorize('update', $lead);

        $admin = $request->user();

        $lead->fill($request->safe()->only([
            'customer_name', 'whatsapp_number', 'lead_type',
            'requirements', 'priority', 'notes',
        ]));

        if ($request->safe()->has('follow_up_at')) {
            $followUpAt = $request->safe()->only('follow_up_at')['follow_up_at'] ?? null;

            if ($followUpAt === null) {
                $lead->follow_up_at = null;
                $lead->save();
            } else {
                $lead->save();
                $this->leads->scheduleFollowUp($lead, new \DateTimeImmutable($followUpAt), $admin);
            }
        } else {
            $lead->save();
        }

        if ($request->safe()->has('status')) {
            $newStatus = $request->safe()->only('status')['status'];

            if ($newStatus !== $lead->status) {
                $this->leads->changeStatus($lead, $newStatus, $admin);
            }
        }

        if ($request->safe()->has('assigned_to')) {
            $newAssigneeId = $request->safe()->only('assigned_to')['assigned_to'] ?? null;
            $newAssignee = $newAssigneeId === null ? null : User::find($newAssigneeId);

            if (($newAssignee?->id) !== $lead->assigned_to) {
                $this->leads->assign($lead, $newAssignee, $admin);
            }
        }

        return response()->json(['data' => $lead->fresh()]);
    }

    /**
     * Admin-only deletion.
     */
    public function destroy(Request $request, Lead $lead)
    {
        Gate::authorize('delete', $lead);

        $this->activityLog->log('lead.deleted', $lead, [
            'status' => $lead->status,
        ], $request->user());

        $lead->delete();

        return response()->json(['message' => 'Lead berhasil dihapus.']);
    }

    /**
     * Convert a WhatsApp click into a lead (idempotent).
     */
    public function convert(Request $request)
    {
        Gate::authorize('create', Lead::class);

        $validated = $request->validate([
            'whatsapp_click_id' => ['required', 'integer', Rule::exists('whatsapp_clicks', 'id')],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'lead_type' => ['nullable', Rule::in(Lead::TYPES)],
            'requirements' => ['nullable', 'string', 'max:5000'],
        ]);

        $click = WhatsAppClick::findOrFail($validated['whatsapp_click_id']);

        $lead = $this->leads->convert($click, $validated, $request->user());

        return response()->json(['data' => $lead->fresh()], 201);
    }
}
