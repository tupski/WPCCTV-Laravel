<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

/**
 * Lead authorization scoping (AGENTS.md §9).
 *
 * super_admin / administrator → all leads
 * lead_manager               → only leads assigned to them
 * everyone else              → none
 */
class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage-leads');
    }

    public function view(User $user, Lead $lead): bool
    {
        if (! $user->can('manage-leads')) {
            return false;
        }

        if ($this->seesAllLeads($user)) {
            return true;
        }

        return $lead->assigned_to !== null && $lead->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage-leads');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead);
    }

    /**
     * Whether the user may change who a lead is assigned to.
     *
     * Rule (audit M5) — decided, not inherited from `update()`:
     *
     *   super_admin / administrator → may reassign any visible lead to anyone
     *     (or unassign it);
     *   lead_manager               → may NOT reassign at all.
     *
     * The alternative — letting a lead_manager move leads "among managers they
     * own" — was rejected because a lead_manager owns leads, not managers, and
     * there is no ownership graph in the schema that could express "manager I
     * am allowed to hand work to". Any such rule would have to be invented,
     * and an invented scope is indistinguishable from no scope. Reassignment
     * is a distribution-of-work decision, so it requires the same authority as
     * `manage-settings`, which a lead_manager does not hold.
     *
     * This is deliberately separate from `update()`: a lead_manager keeps full
     * ability to work the lead they own (status, notes, follow-ups) and simply
     * cannot hand it away or pull one in.
     */
    public function reassign(User $user, Lead $lead): bool
    {
        if (! $this->view($user, $lead)) {
            return false;
        }

        return $user->can('manage-settings');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('manage-settings') || $user->can('manage-users');
    }

    /**
     * Whether the user may see every lead regardless of assignment.
     */
    public function seesAllLeads(User $user): bool
    {
        return $user->hasRole(User::ROLE_SUPER_ADMIN, User::ROLE_ADMINISTRATOR);
    }
}
