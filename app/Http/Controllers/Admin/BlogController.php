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
        // Authorship is server-controlled: ignore any user_id from the payload.
        $data = $request->safe()->except(['tag_ids', 'user_id']);
        $data['user_id'] = auth()->id();

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
        // Authorship never changes via update: ignore any user_id from the payload.
        $data = $request->safe()->except(['tag_ids', 'user_id']);

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
