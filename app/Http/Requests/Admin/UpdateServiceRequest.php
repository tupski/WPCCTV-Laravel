<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Service;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    public function rules(): array
    {
        $id = $this->route('service')->id;

        return [
            'type' => ['required', 'string', Rule::in(['installation', 'repair', 'product_only']), Rule::unique(Service::class, 'type')->ignore($id)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(Service::class, 'slug')->ignore($id)],
            'description' => ['nullable', 'string', 'max:50000'],
            'scope' => ['nullable', 'array'],
            'scope.*' => ['string', 'max:500'],
            'applications' => ['nullable', 'array'],
            'applications.*' => ['string', 'max:500'],
            'process' => ['nullable', 'array'],
            'process.*' => ['string', 'max:500'],
            'published' => ['boolean'],
        ];
    }
}
