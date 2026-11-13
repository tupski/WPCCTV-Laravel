<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\BlogCategory;

class StoreBlogCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(BlogCategory::class, 'name')],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(BlogCategory::class, 'slug')],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
