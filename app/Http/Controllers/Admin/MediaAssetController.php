<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaAssetRequest;
use App\Models\MediaAsset;

class MediaAssetController extends Controller
{
    public function index()
    {
        $assets = MediaAsset::latest()->paginate(25);

        return response()->json(['data' => $assets, 'meta' => $assets->toArray()['meta'] ?? null]);
    }

    public function store(StoreMediaAssetRequest $request)
    {
        $asset = MediaAsset::create($request->safe()->all());

        return response()->json(['data' => $asset], 201);
    }

    public function destroy(MediaAsset $asset)
    {
        $asset->delete();

        return response()->json(['message' => 'Aset media berhasil dihapus.']);
    }
}
