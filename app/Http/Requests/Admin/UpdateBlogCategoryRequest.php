<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\BlogCategory;

class UpdateBlogCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    public function rules(): array
    {
        $id = $this->route('category')->id ?? $this->route('blog_category')->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(BlogCategory::class, 'name')->ignore($id)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(BlogCategory::class, 'slug')->ignore($id)],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
