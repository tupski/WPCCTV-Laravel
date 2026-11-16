<?php

namespace App\Http\Requests\Admin;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-leads');
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['nullable', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'lead_type' => ['nullable', Rule::in(Lead::TYPES)],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'follow_up_at' => ['nullable', 'date'],
        ];
    }
}
