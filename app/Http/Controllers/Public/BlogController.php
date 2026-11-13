<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Services\SeoMetadataResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(protected SeoMetadataResolver $seo)
    {
    }

    public function index(Request $request): View
    {
        $query = BlogArticle::published()->with(['category', 'tags', 'seo']);

        if ($categorySlug = $request->input('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($tagSlug = $request->input('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $tagSlug));
        }

        $articles = $query->latest('published_at')->paginate(10)->appends($request->query());
        $categories = BlogCategory::all();

        $seoData = $this->seo->resolve($articles->first() ?? new BlogArticle(['title' => 'Blog']));

        return view('public.blog.index', compact('articles', 'categories', 'seoData'));
    }

    public function show(string $slug): View
    {
        $article = BlogArticle::published()
            ->where('slug', $slug)
            ->with(['author', 'category', 'tags', 'seo'])
            ->firstOrFail();

        $seoData = $this->seo->resolve($article);

        return view('public.blog.show', compact('article', 'seoData'));
    }
}
