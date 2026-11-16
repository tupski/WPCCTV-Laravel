<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HTML assignment form. `assigned_to` may be blank to unassign.
 *
 * Authorization is split in two (audit M5):
 *
 *  - `authorize()` enforces the `reassign` ability, so a lead_manager cannot
 *    move a lead at all — not to a third party and not to themselves;
 *  - `rules()` narrows the *target* for whoever can reassign, so an
 *    administrator can only pick an existing user.
 *
 * Both checks live here, server-side. The view hides the control from a
 * lead_manager as a courtesy, never as the enforcement.
 */
class AssignLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reassign', $this->route('lead'));
    }

    public function rules(): array
    {
        return [
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.exists' => 'Pengguna yang dipilih tidak ditemukan.',
        ];
    }
}
