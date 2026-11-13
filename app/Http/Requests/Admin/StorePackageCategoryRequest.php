<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\PackageCategory;

class StorePackageCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(PackageCategory::class, 'name')],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(PackageCategory::class, 'slug')],
        ];
    }
}
