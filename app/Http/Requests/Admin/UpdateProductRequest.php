<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Product;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    public function rules(): array
    {
        $id = $this->route('product')->id;

        return [
            'brand_id' => ['required', 'integer', Rule::exists('product_brands', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(Product::class, 'slug')->ignore($id)],
            'model' => ['nullable', 'string', 'max:255'],
            'camera_type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:50000'],
            'key_features' => ['nullable', 'array'],
            'key_features.*' => ['string', 'max:500'],
            'technical_specs' => ['nullable', 'array'],
            'technical_specs.*' => ['string', 'max:500'],
            'recommended_applications' => ['nullable', 'array'],
            'recommended_applications.*' => ['string', 'max:500'],
            'image' => ['nullable', 'string', 'max:255'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['string', 'max:255'],
            'published' => ['boolean'],
        ];
    }
}
