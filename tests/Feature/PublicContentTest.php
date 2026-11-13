<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\Page;
use App\Models\Package;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Services\SeoMetadataResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_product_index_shows_only_published(): void
    {
        $published = Product::factory()->published()->create(['name' => 'Published Cam']);
        $draft = Product::factory()->create(['name' => 'Draft Cam']);

        $response = $this->get('/products');

        $response->assertOk();
        $response->assertSee('Published Cam');
        $response->assertDontSee('Draft Cam');
    }

    public function test_public_product_detail_shows_published_by_slug(): void
    {
        $product = Product::factory()->published()->create(['name' => 'Test Product', 'slug' => 'test-product']);

        $response = $this->get('/products/test-product');

        $response->assertOk();
        $response->assertSee('Test Product');
    }

    public function test_public_product_detail_returns_404_for_draft(): void
    {
        $product = Product::factory()->create(['name' => 'Draft Product', 'slug' => 'draft-product']);

        $this->get('/products/draft-product')->assertNotFound();
    }

    public function test_public_package_index_shows_only_published(): void
    {
        $published = Package::factory()->published()->create(['title' => 'Published Pkg']);
        $draft = Package::factory()->create(['title' => 'Draft Pkg']);

        $response = $this->get('/packages');

        $response->assertOk();
        $response->assertSee('Published Pkg');
        $response->assertDontSee('Draft Pkg');
    }

    public function test_public_service_index_shows_only_published(): void
    {
        $published = Service::factory()->published()->create(['title' => 'Instalation Service']);
        $draft = Service::factory()->create(['title' => 'Repair Service']);

        $response = $this->get('/services');

        $response->assertOk();
        $response->assertSee('Instalation Service');
        $response->assertDontSee('Repair Service');
    }

    public function test_public_blog_index_shows_only_published(): void
    {
        $user = User::factory()->create();
        $published = BlogArticle::factory()->published()->create([
            'user_id' => $user->id,
            'title' => 'Published Article',
        ]);
        $draft = BlogArticle::factory()->create([
            'user_id' => $user->id,
            'title' => 'Draft Article',
        ]);

        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertSee('Published Article');
        $response->assertDontSee('Draft Article');
    }

    public function test_public_page_detail_shows_published_by_slug(): void
    {
        $page = Page::factory()->published()->create(['title' => 'About Us', 'slug' => 'about-us']);

        $response = $this->get('/pages/about-us');

        $response->assertOk();
        $response->assertSee('About Us');
    }

    public function test_public_page_detail_returns_404_for_draft(): void
    {
        $page = Page::factory()->create(['title' => 'Secret Page', 'slug' => 'secret-page']);

        $this->get('/pages/secret-page')->assertNotFound();
    }

    public function test_seo_metadata_resolver_returns_fallback_chain(): void
    {
        $resolver = app(SeoMetadataResolver::class);
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'Product description',
            'image' => '/images/test.jpg',
            'slug' => 'test-product',
        ]);

        $result = $resolver->resolve($product);

        $this->assertArrayHasKey('seo_title', $result);
        $this->assertArrayHasKey('meta_description', $result);
        $this->assertArrayHasKey('canonical', $result);
        $this->assertArrayHasKey('robots', $result);
        $this->assertArrayHasKey('og_title', $result);
        $this->assertArrayHasKey('og_description', $result);
        $this->assertArrayHasKey('og_image', $result);
        $this->assertArrayHasKey('og_type', $result);
        $this->assertArrayHasKey('twitter_card', $result);
        $this->assertArrayHasKey('twitter_title', $result);
        $this->assertArrayHasKey('twitter_description', $result);
        $this->assertArrayHasKey('twitter_image', $result);

        // Model title fallback
        $this->assertSame('Test Product', $result['seo_title']);
        // Model description fallback
        $this->assertSame('Product description', $result['meta_description']);
        // Model image fallback (absolute URL)
        $this->assertNotNull($result['og_image']);
        $this->assertStringContainsString('/images/test.jpg', $result['og_image']);
        // Default OG type
        $this->assertSame('website', $result['og_type']);
        // Default Twitter card
        $this->assertSame('summary_large_image', $result['twitter_card']);
        // Default robots
        $this->assertSame('index, follow', $result['robots']);
    }

    public function test_seo_metadata_resolver_explicit_seo_overrides_model(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
        ]);

        \App\Models\SeoMetadata::create([
            'seoable_type' => $product->getMorphClass(),
            'seoable_id' => $product->id,
            'seo_title' => 'Explicit SEO Title',
            'meta_description' => 'Explicit meta description',
            'og_image' => '/custom/og.jpg',
        ]);

        $result = app(SeoMetadataResolver::class)->resolve($product->refresh());

        $this->assertSame('Explicit SEO Title', $result['seo_title']);
        $this->assertSame('Explicit meta description', $result['meta_description']);
        $this->assertStringContainsString('/custom/og.jpg', $result['og_image']);
    }

    public function test_seo_metadata_resolver_falls_back_to_model_title_when_no_seo_record(): void
    {
        $product = Product::factory()->create([
            'name' => 'Minimal Product',
            'description' => null,
            'image' => null,
            'slug' => 'minimal-product',
        ]);

        $result = app(SeoMetadataResolver::class)->resolve($product);

        // Model title takes priority over global config default
        $this->assertSame('Minimal Product', $result['seo_title']);
    }

    public function test_seo_metadata_resolver_uses_global_defaults_for_empty_model(): void
    {
        // Use a minimal model that has no title/name/description/image attributes
        $emptyProduct = new Product();
        $result = app(SeoMetadataResolver::class)->resolve($emptyProduct);

        // Falls back to global config defaults when model has no data
        $this->assertSame('WP CCTV Indonesia', $result['seo_title']);
        $this->assertSame('index, follow', $result['robots']);
    }
}
