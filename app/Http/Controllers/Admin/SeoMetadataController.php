<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeoMetadataRequest;
use App\Http\Requests\Admin\UpdateSeoMetadataRequest;
use App\Models\SeoMetadata;
use App\Services\SeoMetadataResolver;

class SeoMetadataController extends Controller
{
    public function __construct(protected SeoMetadataResolver $resolver)
    {
    }

    public function index()
    {
        $records = SeoMetadata::with('seoable')->latest()->paginate(25);

        return response()->json(['data' => $records, 'meta' => $records->toArray()['meta'] ?? null]);
    }

    public function store(StoreSeoMetadataRequest $request)
    {
        $record = SeoMetadata::updateOrCreate(
            ['seoable_type' => $request->input('seoable_type'), 'seoable_id' => $request->input('seoable_id')],
            $request->safe()->except(['seoable_type', 'seoable_id'])
        );

        return response()->json(['data' => $record], 201);
    }

    public function update(UpdateSeoMetadataRequest $request, SeoMetadata $seoMetadata)
    {
        $seoMetadata->update($request->safe()->except(['seoable_type', 'seoable_id']));

        return response()->json(['data' => $seoMetadata->fresh()]);
    }

    public function destroy(SeoMetadata $seoMetadata)
    {
        $seoMetadata->delete();

        return response()->json(['message' => 'Metadata SEO berhasil dihapus.']);
    }
}
