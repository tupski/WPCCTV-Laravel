<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductBrand;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('brand')->latest()->paginate(25);

        return response()->json(['data' => $products, 'meta' => $products->toArray()['meta'] ?? null]);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->safe()->except(['category_ids', 'usage_category_ids', 'tag_ids']);

        $product = Product::create($data);

        return response()->json(['data' => $product], 201);
    }

    public function show(Product $product)
    {
        $product->load('brand');

        return response()->json(['data' => $product]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->safe()->only([
            'brand_id', 'name', 'slug', 'model', 'camera_type', 'description',
            'key_features', 'technical_specs', 'recommended_applications',
            'image', 'gallery', 'published',
        ]));

        return response()->json(['data' => $product->fresh()]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }
}
