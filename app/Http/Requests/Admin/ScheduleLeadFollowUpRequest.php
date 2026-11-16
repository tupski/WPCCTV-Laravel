<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HTML follow-up date form. The date is required here because clearing a
 * follow-up is not offered from the detail screen's primary action.
 */
class ScheduleLeadFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    public function rules(): array
    {
        return [
            'follow_up_at' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'follow_up_at.required' => 'Isi tanggal tindak lanjut.',
            'follow_up_at.date' => 'Tanggal tindak lanjut tidak valid.',
        ];
    }
}
