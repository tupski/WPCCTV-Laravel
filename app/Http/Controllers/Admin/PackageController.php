<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePackageRequest;
use App\Http\Requests\Admin\UpdatePackageRequest;
use App\Models\Package;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::with(['brand', 'categories', 'usageCategories'])
            ->latest()
            ->paginate(25);

        return response()->json(['data' => $packages, 'meta' => $packages->toArray()['meta'] ?? null]);
    }

    public function store(StorePackageRequest $request)
    {
        $data = $request->safe()->except(['category_ids', 'usage_category_ids']);

        $package = Package::create($data);

        $package->categories()->sync($request->input('category_ids', []));
        $package->usageCategories()->sync($request->input('usage_category_ids', []));

        return response()->json(['data' => $package], 201);
    }

    public function show(Package $package)
    {
        $package->load('brand', 'categories', 'usageCategories');

        return response()->json(['data' => $package]);
    }

    public function update(UpdatePackageRequest $request, Package $package)
    {
        $data = $request->safe()->except(['category_ids', 'usage_category_ids']);

        $package->update($data);

        $package->categories()->sync($request->input('category_ids', []));
        $package->usageCategories()->sync($request->input('usage_category_ids', []));

        return response()->json(['data' => $package->fresh()]);
    }

    public function destroy(Package $package)
    {
        $package->delete();

        return response()->json(['message' => 'Paket berhasil dihapus.']);
    }
}
