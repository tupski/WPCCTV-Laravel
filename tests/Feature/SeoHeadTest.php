<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use App\Models\SeoMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoHeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_renders_seo_record_values_in_head(): void
    {
        $article = BlogArticle::factory()->published()->create([
            'title' => 'Tips Memilih CCTV',
        ]);

        SeoMetadata::factory()->create([
            'seoable_type' => $article->getMorphClass(),
            'seoable_id' => $article->id,
            'seo_title' => 'Record SEO Title',
            'meta_description' => 'Record SEO Description',
            'canonical' => '/blog/custom-canonical',
            'og_title' => 'Record OG Title',
            'og_description' => 'Record OG Description',
            'og_image' => 'media/og-image.jpg',
        ]);

        $response = $this->get("/blog/{$article->slug}");

        $response->assertOk();
        $response->assertSee('Record SEO Title', false);
        $response->assertSee('Record SEO Description', false);
        $response->assertSee('Record OG Title', false);
        $response->assertSee('Record OG Description', false);
        // Canonical resolved to absolute URL from the record value.
        $response->assertSee('<link rel="canonical" href="'.url('/blog/custom-canonical').'"', false);
        // OG image absolute.
        $response->assertSee('<meta property="og:image" content="'.asset('media/og-image.jpg').'"', false);
    }

    public function test_show_page_without_seo_record_uses_current_url_canonical(): void
    {
        $article = BlogArticle::factory()->published()->create([
            'title' => 'Tanpa SEO Record',
        ]);

        $response = $this->get("/blog/{$article->slug}");

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.url("/blog/{$article->slug}").'"', false);
        // Resolver fallback chain: model title wins over config default.
        $response->assertSee('<title>'.$article->title.'</title>', false);
        // Config defaults for twitter:card and robots.
        $response->assertSee('<meta name="twitter:card" content="'.config('seo.defaults.twitter_card').'"', false);
        $response->assertSee('<meta name="robots" content="'.config('seo.defaults.robots').'"', false);
    }
}
