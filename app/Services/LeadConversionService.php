<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Models\WhatsAppClick;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/**
 * Lead lifecycle transitions. Every mutation writes a lead_activities audit row;
 * status changes additionally write an activity_logs row through ActivityLogService.
 */
class LeadConversionService
{
    public function __construct(protected ActivityLogService $activityLog) {}

    /**
     * Convert a WhatsApp click into an identified lead.
     *
     * Idempotent: a click that already produced a lead returns that lead
     * instead of creating a duplicate.
     */
    public function convert(WhatsAppClick $click, array $data, User $admin): Lead
    {
        return DB::transaction(function () use ($click, $data, $admin): Lead {
            $existing = Lead::where('whatsapp_click_id', $click->id)->first();

            if ($existing !== null) {
                return $existing;
            }

            if ($click->converted_lead_id !== null) {
                $linked = Lead::find($click->converted_lead_id);

                if ($linked !== null) {
                    return $linked;
                }
            }

            $lead = Lead::create([
                'whatsapp_click_id' => $click->id,
                'customer_name' => $data['customer_name'] ?? null,
                'whatsapp_number' => $data['whatsapp_number'] ?? null,
                'lead_type' => $data['lead_type'] ?? null,
                'requirements' => $data['requirements'] ?? null,
                'status' => Lead::STATUS_NEW,
                // Server-forced: never taken from request input.
                'assigned_to' => $admin->id,
                'converted_at' => now(),
            ]);

            $this->recordActivity(
                $lead,
                $admin,
                LeadActivity::ACTION_CONVERT,
                null,
                null,
                null,
                $click->id,
            );

            $click->update(['converted_lead_id' => $lead->id]);

            $this->log($lead, $admin, 'lead.converted', [
                'whatsapp_click_id' => $click->id,
            ]);

            return $lead;
        });
    }

    /**
     * Change a lead status and audit the transition.
     */
    public function changeStatus(Lead $lead, string $newStatus, ?User $admin): void
    {
        $oldStatus = $lead->status;

        $lead->status = $newStatus;

        if (
            in_array($newStatus, [Lead::STATUS_WON, Lead::STATUS_QUALIFIED], true)
            && $lead->converted_at === null
        ) {
            $lead->converted_at = now();
        }

        $lead->save();

        $this->recordActivity(
            $lead,
            $admin,
            LeadActivity::ACTION_STATUS_CHANGE,
            'status',
            $oldStatus,
            $newStatus,
        );

        $this->log($lead, $admin, 'lead.status_changed', [
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
        ]);
    }

    /**
     * Append a free-text note to a lead timeline.
     */
    public function addNote(Lead $lead, string $note, User $admin): LeadActivity
    {
        return $this->recordActivity(
            $lead,
            $admin,
            LeadActivity::ACTION_NOTE,
            null,
            null,
            $note,
        );
    }

    /**
     * Reassign a lead and audit the change.
     */
    public function assign(Lead $lead, ?User $newAssignee, User $admin): void
    {
        $oldAssignee = $lead->assigned_to;

        $lead->assigned_to = $newAssignee?->id;
        $lead->save();

        $this->recordActivity(
            $lead,
            $admin,
            LeadActivity::ACTION_ASSIGN,
            'assigned_to',
            $oldAssignee === null ? null : (string) $oldAssignee,
            $newAssignee === null ? null : (string) $newAssignee->id,
        );

        $this->log($lead, $admin, 'lead.assigned', [
            'old_value' => $oldAssignee,
            'new_value' => $newAssignee?->id,
        ]);
    }

    /**
     * Schedule a follow-up and audit the change.
     */
    public function scheduleFollowUp(Lead $lead, DateTimeInterface $at, User $admin): void
    {
        $oldFollowUp = $lead->follow_up_at;

        $lead->follow_up_at = $at;
        $lead->save();

        $this->recordActivity(
            $lead,
            $admin,
            LeadActivity::ACTION_FOLLOWUP,
            'follow_up_at',
            $oldFollowUp?->toDateTimeString(),
            $at->format('Y-m-d H:i:s'),
        );

        $this->log($lead, $admin, 'lead.followup_scheduled', [
            'old_value' => $oldFollowUp?->toDateTimeString(),
            'new_value' => $at->format('Y-m-d H:i:s'),
        ]);
    }

    protected function recordActivity(
        Lead $lead,
        ?User $user,
        string $action,
        ?string $field = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?int $whatsappClickId = null,
    ): LeadActivity {
        return LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => $user?->id,
            'action' => $action,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'whatsapp_click_id' => $whatsappClickId,
            'created_at' => now(),
        ]);
    }

    protected function log(Lead $lead, ?User $user, string $action, array $context): ?ActivityLog
    {
        return $this->activityLog->log($action, $lead, $context, $user);
    }
}
