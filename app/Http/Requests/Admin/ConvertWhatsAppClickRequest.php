<?php

namespace App\Http\Requests\Admin;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HTML conversion form for a WhatsApp click.
 *
 * Authorization is the `create` ability, matching the JSON endpoint; the
 * click itself is authorized by the route-model-bound parameter.
 */
class ConvertWhatsAppClickRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Lead::class);
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['nullable', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'lead_type' => ['nullable', Rule::in(Lead::TYPES)],
            'requirements' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'lead_type.in' => 'Jenis kebutuhan yang dipilih tidak dikenal.',
        ];
    }
}
