<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePackageCategoryRequest;
use App\Http\Requests\Admin\UpdatePackageCategoryRequest;
use App\Models\PackageCategory;

class PackageCategoryController extends Controller
{
    public function index()
    {
        $categories = PackageCategory::withCount('packages')->latest()->paginate(25);

        return response()->json(['data' => $categories, 'meta' => $categories->toArray()['meta'] ?? null]);
    }

    public function store(StorePackageCategoryRequest $request)
    {
        $category = PackageCategory::create($request->safe()->all());

        return response()->json(['data' => $category], 201);
    }

    public function update(UpdatePackageCategoryRequest $request, PackageCategory $category)
    {
        $category->update($request->safe()->all());

        return response()->json(['data' => $category->fresh()]);
    }

    public function destroy(PackageCategory $category)
    {
        $category->delete();

        return response()->json(['message' => 'Kategori paket berhasil dihapus.']);
    }
}
