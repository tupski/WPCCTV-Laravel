<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ProductBrand;

class UpdateProductBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    public function rules(): array
    {
        $id = $this->route('brand')->id ?? $this->route('product_brand')->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(ProductBrand::class, 'name')->ignore($id)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(ProductBrand::class, 'slug')->ignore($id)],
            'logo' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
