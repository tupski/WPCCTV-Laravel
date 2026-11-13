<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Package;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['nullable', 'integer', Rule::exists('package_brands', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(Package::class, 'slug')],
            'description' => ['nullable', 'string', 'max:50000'],
            'hero_image' => ['nullable', 'string', 'max:255'],
            'camera_quantity' => ['nullable', 'integer', 'in:1,2,4,8,16'],
            'included_equipment' => ['nullable', 'array'],
            'included_equipment.*' => ['string', 'max:500'],
            'installation_scope' => ['nullable', 'array'],
            'installation_scope.*' => ['string', 'max:500'],
            'relevant_specs' => ['nullable', 'array'],
            'relevant_specs.*' => ['string', 'max:500'],
            'installation_process' => ['nullable', 'array'],
            'installation_process.*' => ['string', 'max:500'],
            'exclusions' => ['nullable', 'array'],
            'exclusions.*' => ['string', 'max:500'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'published' => ['boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('package_categories', 'id')],
            'usage_category_ids' => ['nullable', 'array'],
            'usage_category_ids.*' => ['integer', Rule::exists('package_usage_categories', 'id')],
        ];
    }
}
