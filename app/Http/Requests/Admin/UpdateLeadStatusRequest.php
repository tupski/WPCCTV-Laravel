<?php

namespace App\Http\Requests\Admin;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HTML status form. Authorization mirrors UpdateLeadRequest: the lead policy
 * decides, so a lead_manager cannot change someone else's lead by guessing
 * an id.
 */
class UpdateLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Lead::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Pilih status baru sebelum menyimpan.',
            'status.in' => 'Status yang dipilih tidak dikenal.',
        ];
    }
}
