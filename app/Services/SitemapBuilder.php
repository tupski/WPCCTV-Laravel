<?php

namespace App\Services;

use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageUsageCategory;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\Service;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

/**
 * XML sitemap for the public content set.
 *
 * Only published content is listed — a sitemap that advertises a URL the site
 * 404s is worse than no sitemap at all. The document is cached because
 * building it costs a dozen queries and it changes only when content is
 * edited (`forget()` is available for the admin save path).
 */
class SitemapBuilder
{
    public const CACHE_KEY = 'sitemap.xml';

    public const CACHE_TTL_SECONDS = 3600;

    public function xml(): string
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn (): string => $this->build());
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function build(): string
    {
        $urls = [];

        $urls[] = $this->entry(url('/'), now(), 'daily', '1.0');

        $staticRoutes = [
            ['products.index', '0.9'],
            ['packages.index', '1.0'],
            ['services.index', '0.9'],
            ['blog.index', '0.8'],
            ['faqs.index', '0.6'],
            ['contact.show', '0.7'],
        ];

        foreach ($staticRoutes as [$route, $priority]) {
            $urls[] = $this->entry(route($route), null, 'weekly', $priority);
        }

        foreach (Product::published()->orderBy('id')->get(['slug', 'updated_at']) as $product) {
            $urls[] = $this->entry(route('products.show', $product->slug), $product->updated_at, 'monthly', '0.7');
        }

        foreach (Package::published()->orderBy('id')->get(['slug', 'updated_at']) as $package) {
            $urls[] = $this->entry(route('packages.show', $package->slug), $package->updated_at, 'monthly', '0.9');
        }

        foreach (Service::published()->orderBy('id')->get(['slug', 'updated_at']) as $service) {
            $urls[] = $this->entry(route('services.show', $service->slug), $service->updated_at, 'monthly', '0.7');
        }

        foreach (BlogArticle::published()->orderBy('id')->get(['slug', 'updated_at']) as $article) {
            $urls[] = $this->entry(route('blog.show', $article->slug), $article->updated_at, 'monthly', '0.6');
        }

        foreach (Page::published()->orderBy('id')->get(['slug', 'updated_at']) as $page) {
            $urls[] = $this->entry(route('pages.show', $page->slug), $page->updated_at, 'yearly', '0.5');
        }

        // Archives are indexable, so they belong in the map — but only when
        // they are not empty, otherwise they would list a thin page.
        $publishedArticle = fn ($query) => $query
            ->where('status', 'published')
            ->whereNotNull('published_at');

        foreach (BlogCategory::query()->whereHas('articles', $publishedArticle)->orderBy('id')->get(['slug']) as $category) {
            $urls[] = $this->entry(route('blog.index', ['category' => $category->slug]), null, 'weekly', '0.5');
        }

        foreach (BlogTag::query()->whereHas('articles', $publishedArticle)->orderBy('id')->get(['slug']) as $tag) {
            $urls[] = $this->entry(route('blog.index', ['tag' => $tag->slug]), null, 'weekly', '0.4');
        }

        // Filtered package listings are stable landing pages (a slug, not a
        // query-string permutation), so they are worth indexing.
        $publishedPackage = fn ($query) => $query->where('published', true);

        foreach (PackageUsageCategory::query()->whereHas('packages', $publishedPackage)->orderBy('id')->get(['slug']) as $usage) {
            $urls[] = $this->entry(route('packages.index', ['usage' => $usage->slug]), null, 'weekly', '0.6');
        }

        foreach (PackageCategory::query()->whereHas('packages', $publishedPackage)->orderBy('id')->get(['slug']) as $category) {
            $urls[] = $this->entry(route('packages.index', ['category' => $category->slug]), null, 'weekly', '0.6');
        }

        foreach (ProductBrand::query()->whereHas('products', $publishedPackage)->orderBy('id')->get(['slug']) as $brand) {
            $urls[] = $this->entry(route('products.index', ['brand' => $brand->slug]), null, 'weekly', '0.5');
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .implode('', $urls)
            .'</urlset>'."\n";
    }

    protected function entry(string $loc, ?DateTimeInterface $lastmod, string $changefreq, string $priority): string
    {
        $xml = '  <url><loc>'.e($loc).'</loc>';

        if ($lastmod !== null) {
            $xml .= '<lastmod>'.$lastmod->format('Y-m-d').'</lastmod>';
        }

        return $xml.'<changefreq>'.$changefreq.'</changefreq><priority>'.$priority.'</priority></url>'."\n";
    }
}
