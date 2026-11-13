<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\Page;
use App\Models\Package;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_auto_generated_from_name_on_create(): void
    {
        $brand = ProductBrand::create(['name' => 'Test Brand Co']);

        $this->assertSame('test-brand-co', $brand->slug);
    }

    public function test_slug_unique_for_same_content_type(): void
    {
        ProductBrand::create(['name' => 'Test Brand']);
        $second = ProductBrand::create(['name' => 'Test Brand']);

        $this->assertNotSame(ProductBrand::first()->slug, $second->slug);
    }

    public function test_manual_slug_override_accepted(): void
    {
        $brand = ProductBrand::create(['name' => 'My Brand', 'slug' => 'custom-slug']);

        $this->assertSame('custom-slug', $brand->slug);
    }

    public function test_slug_generates_unique_for_pages(): void
    {
        Page::create(['title' => 'About Us', 'body' => 'Content A']);
        $second = Page::create(['title' => 'About Us', 'body' => 'Content B']);

        $this->assertNotSame(Page::first()->slug, $second->slug);
    }

    public function test_slug_generates_unique_for_packages(): void
    {
        Package::create(['title' => 'CCTV 4 Kamera']);
        $second = Package::create(['title' => 'CCTV 4 Kamera']);

        $this->assertNotSame(Package::first()->slug, $second->slug);
    }

    public function test_slug_generates_unique_for_blog_articles(): void
    {
        $user = \App\Models\User::factory()->create();
        BlogArticle::create([
            'user_id' => $user->id,
            'title' => 'Test Article',
            'body' => 'Content',
            'status' => 'draft',
        ]);
        $second = BlogArticle::create([
            'user_id' => $user->id,
            'title' => 'Test Article',
            'body' => 'Content',
            'status' => 'draft',
        ]);

        $this->assertNotSame(BlogArticle::first()->slug, $second->slug);
    }

    public function test_slug_generates_unique_for_services(): void
    {
        Service::create(['type' => 'installation', 'title' => 'Instalasi CCTV']);
        Service::create(['type' => 'repair', 'title' => 'Instalasi CCTV']);
        $third = Service::create(['type' => 'product_only', 'title' => 'Instalasi CCTV']);

        $services = Service::where('title', 'Instalasi CCTV')->get();
        $slugs = $services->pluck('slug')->unique();

        $this->assertCount(3, $slugs);
    }

    public function test_slug_generates_unique_for_blog_categories(): void
    {
        BlogCategory::create(['name' => 'Technology']);
        $second = BlogCategory::create(['name' => 'Technology']);

        $this->assertNotSame(BlogCategory::first()->slug, $second->slug);
    }
}
