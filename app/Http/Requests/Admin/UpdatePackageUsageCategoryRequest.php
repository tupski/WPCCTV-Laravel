<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\PackageUsageCategory;

class UpdatePackageUsageCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    public function rules(): array
    {
        $id = $this->route('usage_category')->id ?? $this->route('package_usage_category')->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(PackageUsageCategory::class, 'name')->ignore($id)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(PackageUsageCategory::class, 'slug')->ignore($id)],
        ];
    }
}
