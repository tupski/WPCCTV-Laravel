<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    public function rules(): array
    {
        return [
            'whatsapp_clicks_enabled' => ['sometimes', 'boolean'],
            'lead_updates_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
