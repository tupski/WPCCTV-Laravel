<?php

namespace App\Http\Requests\Admin;

use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    public function rules(): array
    {
        return [
            'action' => [
                'required',
                Rule::in([LeadActivity::ACTION_NOTE, LeadActivity::ACTION_IDENTIFY]),
            ],
            'note' => ['nullable', 'string', 'max:5000', 'required_if:action,'.LeadActivity::ACTION_NOTE],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'lead_type' => ['nullable', Rule::in(Lead::TYPES)],
        ];
    }
}
