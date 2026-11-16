<?php

namespace App\Http\Requests\Admin;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HTML note form. Notes are append-only, so only the body is validated.
 */
class StoreLeadNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:5000'],
            'lead_type' => ['sometimes', 'nullable', Rule::in(Lead::TYPES)],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Tulis isi catatan sebelum menyimpan.',
            'note.max' => 'Catatan maksimal 5000 karakter.',
        ];
    }
}
