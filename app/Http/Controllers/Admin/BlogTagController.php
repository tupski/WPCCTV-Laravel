<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogTagRequest;
use App\Http\Requests\Admin\UpdateBlogTagRequest;
use App\Models\BlogTag;

class BlogTagController extends Controller
{
    public function index()
    {
        $tags = BlogTag::withCount('articles')->latest()->paginate(25);

        return response()->json(['data' => $tags, 'meta' => $tags->toArray()['meta'] ?? null]);
    }

    public function store(StoreBlogTagRequest $request)
    {
        $tag = BlogTag::create($request->safe()->all());

        return response()->json(['data' => $tag], 201);
    }

    public function update(UpdateBlogTagRequest $request, BlogTag $tag)
    {
        $tag->update($request->safe()->all());

        return response()->json(['data' => $tag->fresh()]);
    }

    public function destroy(BlogTag $tag)
    {
        $tag->delete();

        return response()->json(['message' => 'Tag berhasil dihapus.']);
    }
}
