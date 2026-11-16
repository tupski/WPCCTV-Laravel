<?php

namespace App\Http\Requests\Admin;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'whatsapp_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'lead_type' => ['sometimes', 'nullable', Rule::in(Lead::TYPES)],
            'requirements' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'priority' => ['sometimes', 'nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'follow_up_at' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', Rule::in(Lead::STATUSES)],
            'assigned_to' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }

    /**
     * `assigned_to` is only a reassignment when it actually moves the lead.
     *
     * The `update` ability covers the whole lead, a lead_manager included, so
     * `authorize()` cannot decide this: a lead_manager may PATCH their own
     * lead's status and notes, but must not use the same request to hand it
     * away (audit M5). The distinction is made per-field here, server-side,
     * and it is a no-op when the submitted assignee equals the current one —
     * so a form that round-trips every field does not fail just because a
     * lead_manager resubmitted the lead it already owns.
     *
     * Null is a real change (unassign), so it is compared with `!==` on the
     * resolved ids rather than loosely.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->has('assigned_to')) {
                return;
            }

            $lead = $this->route('lead');
            $requested = $this->input('assigned_to');

            $requestedId = $requested === null || $requested === '' ? null : (int) $requested;

            if ($requestedId === $lead?->assigned_to) {
                return;
            }

            if (! $this->user()->can('reassign', $lead)) {
                $validator->errors()->add(
                    'assigned_to',
                    'Anda tidak memiliki izin untuk mengubah penugasan lead ini.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'assigned_to.exists' => 'Pengguna yang dipilih tidak ditemukan.',
        ];
    }
}
