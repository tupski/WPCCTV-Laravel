<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Page;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::latest()->paginate(25);

        return response()->json(['data' => $pages, 'meta' => $pages->toArray()['meta'] ?? null]);
    }

    public function store(StorePageRequest $request)
    {
        $page = Page::create($request->safe()->all());

        return response()->json(['data' => $page], 201);
    }

    public function show(Page $page)
    {
        return response()->json(['data' => $page->load('seo')]);
    }

    public function update(UpdatePageRequest $request, Page $page)
    {
        $page->update($request->safe()->all());

        return response()->json(['data' => $page->fresh()]);
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return response()->json(['message' => 'Halaman berhasil dihapus.']);
    }
}
