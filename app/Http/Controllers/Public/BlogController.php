<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Services\SeoMetadataResolver;
use App\Services\StructuredData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(
        protected SeoMetadataResolver $seo,
        protected StructuredData $schema,
    ) {}

    public function index(Request $request): View
    {
        $query = BlogArticle::published()->with(['category', 'tags', 'author', 'seo']);

        $categorySlug = $this->stringOrNull($request->query('category'));
        $tagSlug = $this->stringOrNull($request->query('tag'));
        $search = $this->stringOrNull($request->query('q'));

        if ($categorySlug !== null) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($tagSlug !== null) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $tagSlug));
        }

        if ($search !== null) {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('excerpt', 'like', '%'.$search.'%');
            });
        }

        $articles = $query->latest('published_at')->paginate(10)->appends($request->query());

        $categories = BlogCategory::query()
            ->whereHas('articles', fn ($q) => $q->where('status', 'published')->whereNotNull('published_at'))
            ->orderBy('name')
            ->get();

        $tags = BlogTag::query()
            ->whereHas('articles', fn ($q) => $q->where('status', 'published')->whereNotNull('published_at'))
            ->orderBy('name')
            ->get();

        $activeCategory = $categories->firstWhere('slug', $categorySlug);
        $activeTag = $tags->firstWhere('slug', $tagSlug);

        $heading = 'Blog & Panduan CCTV';
        $description = 'Artikel edukasi seputar pemilihan kamera, instalasi, dan perawatan sistem CCTV.';

        if ($activeCategory !== null) {
            $heading = 'Kategori: '.$activeCategory->name;
            $description = $activeCategory->description ?: $description;
        } elseif ($activeTag !== null) {
            $heading = 'Tag: '.$activeTag->name;
        } elseif ($search !== null) {
            $heading = 'Hasil pencarian "'.$search.'"';
        }

        $seoData = $this->seo->resolve(new BlogArticle(), [
            'seo_title' => $heading.' | WP CCTV Indonesia',
            'meta_description' => $description,
            'canonical' => $categorySlug !== null || $tagSlug !== null || $search !== null
                ? route('blog.index')
                : null,
        ]);

        return view('public.blog.index', [
            'articles' => $articles,
            'categories' => $categories,
            'tags' => $tags,
            'activeCategory' => $activeCategory,
            'activeTag' => $activeTag,
            'search' => $search,
            'heading' => $heading,
            'description' => $description,
            'seoData' => $seoData,
            'jsonLd' => [
                $this->schema->breadcrumbs([
                    ['name' => 'Beranda', 'url' => route('home')],
                    ['name' => 'Blog', 'url' => route('blog.index')],
                ]),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $article = BlogArticle::published()
            ->where('slug', $slug)
            ->with(['author', 'category', 'tags', 'seo'])
            ->firstOrFail();

        $relatedArticles = BlogArticle::published()
            ->whereKeyNot($article->getKey())
            ->when($article->blog_category_id !== null, fn ($q) => $q->where('blog_category_id', $article->blog_category_id))
            ->with(['category', 'author'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        if ($relatedArticles->isEmpty()) {
            $relatedArticles = BlogArticle::published()
                ->whereKeyNot($article->getKey())
                ->with(['category', 'author'])
                ->latest('published_at')
                ->limit(3)
                ->get();
        }

        $seoData = $this->seo->resolve($article);

        return view('public.blog.show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'seoData' => $seoData,
            'jsonLd' => [
                $this->schema->article($article),
                $this->schema->breadcrumbs([
                    ['name' => 'Beranda', 'url' => route('home')],
                    ['name' => 'Blog', 'url' => route('blog.index')],
                    ['name' => $article->title, 'url' => route('blog.show', $article->slug)],
                ]),
            ],
        ]);
    }

    protected function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
