<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Package;
use App\Models\PackageBrand;
use App\Models\PackageCategory;
use App\Models\PackageUsageCategory;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\Service;
use App\Models\SeoMetadata;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_belongs_to_brand(): void
    {
        $brand = ProductBrand::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id]);

        $this->assertSame($brand->id, $product->brand->id);
        $this->assertTrue($product->brand->is($brand));
    }

    public function test_product_has_seo_metadata(): void
    {
        $product = Product::factory()->create();
        $seo = SeoMetadata::factory()->create([
            'seoable_type' => (new Product())->getMorphClass(),
            'seoable_id' => $product->id,
            'seo_title' => 'Custom SEO Title',
        ]);

        $this->assertTrue($product->seo->is($seo));
        $this->assertSame('Custom SEO Title', $product->seo->seo_title);
    }

    public function test_package_belongs_to_brand_and_categories(): void
    {
        $brand = PackageBrand::factory()->create();
        $category = PackageCategory::factory()->create();
        $usage = PackageUsageCategory::factory()->create();
        $package = Package::factory()->create(['brand_id' => $brand->id]);

        $package->categories()->attach($category->id);
        $package->usageCategories()->attach($usage->id);

        $this->assertCount(1, $package->categories()->get());
        $this->assertCount(1, $package->usageCategories()->get());
        $this->assertTrue($package->brand->is($brand));
    }

    public function test_service_belongs_to_faqs(): void
    {
        $service = Service::factory()->create();
        $faq = Faq::factory()->create([
            'context_type' => (new Service())->getMorphClass(),
            'context_id' => $service->id,
        ]);

        $this->assertCount(1, $service->faqs()->get());
        $this->assertTrue($faq->context->is($service));
    }

    public function test_blog_article_has_author_category_and_tags(): void
    {
        $user = User::factory()->create();
        $category = BlogCategory::factory()->create();
        $tag = BlogTag::factory()->create();
        $article = BlogArticle::factory()->published()->create([
            'user_id' => $user->id,
            'blog_category_id' => $category->id,
        ]);

        $article->tags()->attach($tag->id);

        $this->assertTrue($article->author->is($user));
        $this->assertTrue($article->category->is($category));
        $this->assertCount(1, $article->tags);
        $this->assertTrue($article->tags->first()->is($tag));
    }

    public function test_page_has_seo_and_faqs(): void
    {
        $page = Page::factory()->create();
        $faq = Faq::factory()->create([
            'context_type' => (new Page())->getMorphClass(),
            'context_id' => $page->id,
        ]);

        $this->assertCount(1, $page->faqs()->get());
    }

    public function test_faq_context_morph_to_resolves(): void
    {
        $product = Product::factory()->create();
        $faq = Faq::factory()->create([
            'context_type' => $product->getMorphClass(),
            'context_id' => $product->id,
            'question' => 'What is this product?',
        ]);

        $this->assertTrue($faq->context->is($product));
    }
}
