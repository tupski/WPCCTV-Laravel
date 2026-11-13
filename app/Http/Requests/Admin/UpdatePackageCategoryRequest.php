<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\PackageCategory;

class UpdatePackageCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    public function rules(): array
    {
        $id = $this->route('category')->id ?? $this->route('package_category')->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(PackageCategory::class, 'name')->ignore($id)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(PackageCategory::class, 'slug')->ignore($id)],
        ];
    }
}
