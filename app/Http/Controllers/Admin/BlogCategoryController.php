<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogCategoryRequest;
use App\Http\Requests\Admin\UpdateBlogCategoryRequest;
use App\Models\BlogCategory;

class BlogCategoryController extends Controller
{
    public function index()
    {
        $categories = BlogCategory::withCount('articles')->latest()->paginate(25);

        return response()->json(['data' => $categories, 'meta' => $categories->toArray()['meta'] ?? null]);
    }

    public function store(StoreBlogCategoryRequest $request)
    {
        $category = BlogCategory::create($request->safe()->all());

        return response()->json(['data' => $category], 201);
    }

    public function update(UpdateBlogCategoryRequest $request, BlogCategory $category)
    {
        $category->update($request->safe()->all());

        return response()->json(['data' => $category->fresh()]);
    }

    public function destroy(BlogCategory $category)
    {
        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus.']);
    }
}
