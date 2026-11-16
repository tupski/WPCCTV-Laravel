<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-settings');
    }

    /**
     * Normalize the number to digits only.
     *
     * An explicitly blank value clears the setting; anything else keeps its
     * digits so malformed input fails the regex instead of being silently
     * dropped.
     */
    protected function prepareForValidation(): void
    {
        $number = $this->input('whatsapp_number');

        if (! is_string($number)) {
            return;
        }

        if (trim($number) === '') {
            $this->merge(['whatsapp_number' => null]);

            return;
        }

        $digits = preg_replace('/\D+/', '', $number);

        // No digits at all (e.g. "+ -") is malformed input, not an empty value:
        // keep the raw string so the regex rule rejects it instead of Laravel
        // treating a blank string as nullable.
        $this->merge(['whatsapp_number' => $digits === '' ? $number : $digits]);
    }

    public function rules(): array
    {
        return [
            'whatsapp_number' => ['nullable', 'string', 'regex:/^[0-9]{8,15}$/'],
            'retention' => ['nullable', 'array'],
            'retention.page_views_days' => ['required_with:retention', 'integer', 'min:7', 'max:3650'],
            'retention.whatsapp_clicks_days' => ['required_with:retention', 'integer', 'min:7', 'max:3650'],
            'retention.activity_logs_days' => ['required_with:retention', 'integer', 'min:7', 'max:3650'],
        ];
    }

    public function messages(): array
    {
        return [
            'whatsapp_number.regex' => 'Nomor WhatsApp harus terdiri dari 8 sampai 15 digit angka.',
            'retention.array' => 'Konfigurasi retensi harus berupa objek.',
            'retention.*.integer' => 'Nilai retensi harus berupa bilangan bulat.',
            'retention.*.min' => 'Retensi minimal 7 hari.',
            'retention.*.max' => 'Retensi maksimal 3650 hari.',
        ];
    }
}
