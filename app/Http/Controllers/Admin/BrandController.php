<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePackageBrandRequest;
use App\Http\Requests\Admin\StoreProductBrandRequest;
use App\Http\Requests\Admin\UpdatePackageBrandRequest;
use App\Http\Requests\Admin\UpdateProductBrandRequest;
use App\Models\PackageBrand;
use App\Models\ProductBrand;

class BrandController extends Controller
{
    public function productBrands()
    {
        $brands = ProductBrand::withCount('products')->latest()->paginate(25);

        return response()->json(['data' => $brands, 'meta' => $brands->toArray()['meta'] ?? null]);
    }

    public function storeProductBrand(StoreProductBrandRequest $request)
    {
        $brand = ProductBrand::create($request->safe()->all());

        return response()->json(['data' => $brand], 201);
    }

    public function updateProductBrand(UpdateProductBrandRequest $request, ProductBrand $brand)
    {
        $brand->update($request->safe()->all());

        return response()->json(['data' => $brand->fresh()]);
    }

    public function destroyProductBrand(ProductBrand $brand)
    {
        $brand->delete();

        return response()->json(['message' => 'Merek produk berhasil dihapus.']);
    }

    public function packageBrands()
    {
        $brands = PackageBrand::withCount('packages')->latest()->paginate(25);

        return response()->json(['data' => $brands, 'meta' => $brands->toArray()['meta'] ?? null]);
    }

    public function storePackageBrand(StorePackageBrandRequest $request)
    {
        $brand = PackageBrand::create($request->safe()->all());

        return response()->json(['data' => $brand], 201);
    }

    public function updatePackageBrand(UpdatePackageBrandRequest $request, PackageBrand $brand)
    {
        $brand->update($request->safe()->all());

        return response()->json(['data' => $brand->fresh()]);
    }

    public function destroyPackageBrand(PackageBrand $brand)
    {
        $brand->delete();

        return response()->json(['message' => 'Merek paket berhasil dihapus.']);
    }
}
