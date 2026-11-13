<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\PackageUsageCategory;

class StorePackageUsageCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(PackageUsageCategory::class, 'name')],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(PackageUsageCategory::class, 'slug')],
        ];
    }
}
