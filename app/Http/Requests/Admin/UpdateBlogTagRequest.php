<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\BlogTag;

class UpdateBlogTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    public function rules(): array
    {
        $id = $this->route('tag')->id ?? $this->route('blog_tag')->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(BlogTag::class, 'name')->ignore($id)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(BlogTag::class, 'slug')->ignore($id)],
        ];
    }
}
