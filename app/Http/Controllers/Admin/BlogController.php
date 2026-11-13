<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogArticleRequest;
use App\Http\Requests\Admin\UpdateBlogArticleRequest;
use App\Models\BlogArticle;

class BlogController extends Controller
{
    public function index()
    {
        $articles = BlogArticle::with(['author', 'category', 'tags'])
            ->latest()
            ->paginate(25);

        return response()->json(['data' => $articles, 'meta' => $articles->toArray()['meta'] ?? null]);
    }

    public function store(StoreBlogArticleRequest $request)
    {
        $data = $request->safe()->except(['tag_ids']);

        $article = BlogArticle::create($data);

        $article->tags()->sync($request->input('tag_ids', []));

        return response()->json(['data' => $article->fresh()], 201);
    }

    public function show(BlogArticle $article)
    {
        return response()->json(['data' => $article->load('author', 'category', 'tags')]);
    }

    public function update(UpdateBlogArticleRequest $request, BlogArticle $article)
    {
        $data = $request->safe()->except(['tag_ids']);

        $article->update($data);

        $article->tags()->sync($request->input('tag_ids', []));

        return response()->json(['data' => $article->fresh()]);
    }

    public function destroy(BlogArticle $article)
    {
        $article->delete();

        return response()->json(['message' => 'Artikel berhasil dihapus.']);
    }
}
