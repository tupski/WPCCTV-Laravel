<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePackageUsageCategoryRequest;
use App\Http\Requests\Admin\UpdatePackageUsageCategoryRequest;
use App\Models\PackageUsageCategory;

class PackageUsageCategoryController extends Controller
{
    public function index()
    {
        $categories = PackageUsageCategory::withCount('packages')->latest()->paginate(25);

        return response()->json(['data' => $categories, 'meta' => $categories->toArray()['meta'] ?? null]);
    }

    public function store(StorePackageUsageCategoryRequest $request)
    {
        $category = PackageUsageCategory::create($request->safe()->all());

        return response()->json(['data' => $category], 201);
    }

    public function update(UpdatePackageUsageCategoryRequest $request, PackageUsageCategory $usage_category)
    {
        $usage_category->update($request->safe()->all());

        return response()->json(['data' => $usage_category->fresh()]);
    }

    public function destroy(PackageUsageCategory $usage_category)
    {
        $usage_category->delete();

        return response()->json(['message' => 'Kategori penggunaan paket berhasil dihapus.']);
    }
}
