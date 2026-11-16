<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeadActivityRequest;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Services\LeadConversionService;
use Illuminate\Support\Facades\Gate;

class LeadActivityController extends Controller
{
    public function __construct(protected LeadConversionService $leads) {}

    /**
     * Activity timeline for a lead, newest first.
     */
    public function index(Lead $lead)
    {
        Gate::authorize('view', $lead);

        $activities = LeadActivity::where('lead_id', $lead->id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25);

        return response()->json([
            'data' => $activities,
            'meta' => $activities->toArray()['meta'] ?? null,
        ]);
    }

    /**
     * Add a note / identification entry to the lead timeline.
     */
    public function store(StoreLeadActivityRequest $request, Lead $lead)
    {
        Gate::authorize('update', $lead);

        $admin = $request->user();

        if ($request->input('action') === LeadActivity::ACTION_IDENTIFY) {
            $lead->fill($request->safe()->only(['whatsapp_number', 'lead_type']));
            $lead->save();

            $activity = LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => $admin->id,
                'action' => LeadActivity::ACTION_IDENTIFY,
                'field' => 'identity',
                'old_value' => null,
                'new_value' => $request->input('note'),
                'created_at' => now(),
            ]);
        } else {
            $activity = $this->leads->addNote($lead, (string) $request->input('note'), $admin);
        }

        return response()->json(['data' => $activity->fresh()], 201);
    }
}
