<?php

namespace App\Services;

use App\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class SeoMetadataResolver
{
    /**
     * Resolve SEO metadata for a given SEOable model with fallback chain:
     *
     * 1. Explicit page SEO (SeoMetadata record on the model)
     * 2. Model's own attributes (title, description, image)
     * 3. Global defaults from config('seo.defaults')
     *
     * Returns an array with all canonical/robots/OG/Twitter fields.
     * Absolute URLs via url().
     *
     * @param  Model  $seoable  Model with a `seo` morphOne relationship
     * @param  array  $pageDefaults  Optional caller-provided defaults
     * @return array<string, string|null>
     */
    public function resolve(Model $seoable, array $pageDefaults = []): array
    {
        $globalDefaults = Config::get('seo.defaults', []);

        $merged = array_merge($globalDefaults, $pageDefaults);

        $seo = $this->findSeoRecord($seoable);

        $title = $this->firstNonEmpty(
            $seo?->seo_title,
            $this->modelTitle($seoable),
            $merged['seo_title'] ?? null,
        );

        $description = $this->firstNonEmpty(
            $seo?->meta_description,
            $this->modelDescription($seoable),
            $merged['meta_description'] ?? null,
        );

        $canonicalUrl = $this->firstNonEmpty(
            $seo?->canonical,
            $merged['canonical'] ?? null,
        );

        $canonical = $canonicalUrl ? $this->toAbsoluteUrl($canonicalUrl) : null;

        $ogImage = $this->firstNonEmpty(
            $seo?->og_image,
            $this->modelImageUrl($seoable),
            $merged['og_image'] ?? null,
        );

        $ogImage = $ogImage ? $this->toAbsoluteUrl($ogImage) : null;

        $twitterImage = $this->firstNonEmpty(
            $seo?->twitter_image,
            $ogImage,
        );

        $twitterImage = $twitterImage ? $this->toAbsoluteUrl($twitterImage) : null;

        return [
            'seo_title' => $title,
            'meta_description' => $description,
            'canonical' => $canonical,
            'robots' => $seo?->robots ?? $merged['robots'] ?? 'index, follow',
            'og_title' => $this->firstNonEmpty($seo?->og_title, $title),
            'og_description' => $this->firstNonEmpty($seo?->og_description, $description),
            'og_image' => $ogImage,
            'og_type' => $seo?->og_type ?? $merged['og_type'] ?? 'website',
            'twitter_card' => $seo?->twitter_card ?? $merged['twitter_card'] ?? 'summary_large_image',
            'twitter_title' => $this->firstNonEmpty($seo?->twitter_title, $title),
            'twitter_description' => $this->firstNonEmpty($seo?->twitter_description, $description),
            'twitter_image' => $twitterImage,
        ];
    }

    protected function findSeoRecord(Model $model): ?SeoMetadata
    {
        if (! method_exists($model, 'seo')) {
            return null;
        }

        return $model->seo()->first();
    }

    protected function modelTitle(Model $model): ?string
    {
        return $model->title ?? $model->name ?? null;
    }

    protected function modelDescription(Model $model): ?string
    {
        return $model->description ?? $model->excerpt ?? null;
    }

    protected function modelImageUrl(Model $model): ?string
    {
        return $model->image ?? $model->hero_image ?? $model->featured_image ?? null;
    }

    protected function toAbsoluteUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return url($url);
        }

        // Assume relative asset path
        return asset($url);
    }

    protected function firstNonEmpty(?string ...$values): ?string
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
