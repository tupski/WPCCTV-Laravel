<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Services\SeoMetadataResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(protected SeoMetadataResolver $seo)
    {
    }

    public function index(Request $request): View
    {
        $query = Product::published()->with(['brand', 'seo']);

        $brands = ProductBrand::all();

        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($cameraType = $request->input('camera_type')) {
            $query->where('camera_type', $cameraType);
        }

        $products = $query->latest()->paginate(12)->appends($request->query());

        $seoData = $this->seo->resolve($products->first() ?? new Product(['name' => 'Produk']));

        return view('public.products.index', compact('products', 'brands', 'seoData'));
    }

    public function show(string $slug): View
    {
        $product = Product::published()
            ->where('slug', $slug)
            ->with(['brand', 'seo'])
            ->firstOrFail();

        $seoData = $this->seo->resolve($product);

        return view('public.products.show', compact('product', 'seoData'));
    }
}
