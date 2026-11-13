<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Faq;
use App\Models\MediaAsset;
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

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    // ── Authorization tests ────────────────────────────────────────

    public function test_guest_cannot_access_admin_routes(): void
    {
        $this->get('/admin/products')->assertRedirect('/login');
        $this->post('/admin/products')->assertRedirect('/login');
    }

    public function test_non_admin_user_gets_403_on_admin_routes(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($user)->get('/admin/products')->assertForbidden();
        $this->actingAs($user)->post('/admin/products')->assertForbidden();
        $this->actingAs($user)->get('/admin/packages')->assertForbidden();
        $this->actingAs($user)->get('/admin/services')->assertForbidden();
        $this->actingAs($user)->get('/admin/blog')->assertForbidden();
        $this->actingAs($user)->get('/admin/faqs')->assertForbidden();
        $this->actingAs($user)->get('/admin/media')->assertForbidden();
        $this->actingAs($user)->get('/admin/pages')->assertForbidden();
    }

    public function test_admin_user_can_access_admin_routes(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get('/admin/products')->assertOk();
        $this->actingAs($user)->get('/admin/packages')->assertOk();
        $this->actingAs($user)->get('/admin/services')->assertOk();
        $this->actingAs($user)->get('/admin/blog')->assertOk();
        $this->actingAs($user)->get('/admin/faqs')->assertOk();
        $this->actingAs($user)->get('/admin/media')->assertOk();
        $this->actingAs($user)->get('/admin/pages')->assertOk();
    }

    // ── Product CRUD ───────────────────────────────────────────────

    public function test_admin_can_create_product(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $brand = ProductBrand::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/products', [
            'brand_id' => $brand->id,
            'name' => 'Test Camera',
            'model' => 'TC-100',
            'camera_type' => 'dome',
            'published' => false,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['name' => 'Test Camera', 'brand_id' => $brand->id]);
    }

    public function test_admin_can_update_product(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $brand = ProductBrand::factory()->create();
        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($user)->putJson("/admin/products/{$product->id}", [
            'brand_id' => $brand->id,
            'name' => 'New Name',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New Name']);
    }

    public function test_admin_can_delete_product(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->deleteJson("/admin/products/{$product->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_requires_brand_id(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/products', [
            'name' => 'No Brand Product',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['brand_id']);
    }

    public function test_product_published_flag_is_boolean(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $brand = ProductBrand::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/products', [
            'brand_id' => $brand->id,
            'name' => 'Bool Test',
            'published' => 'not-a-bool',
        ]);

        $response->assertStatus(422);
    }

    // ── Package CRUD ───────────────────────────────────────────────

    public function test_admin_can_create_package(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/packages', [
            'title' => '4 Kamera Paket',
            'camera_quantity' => 4,
            'price' => null,
            'published' => false,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('packages', ['title' => '4 Kamera Paket']);
    }

    public function test_package_camera_quantity_validates_in_values(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/packages', [
            'title' => 'Bad Quantity',
            'camera_quantity' => 99,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['camera_quantity']);
    }

    public function test_package_price_is_nullable(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/packages', [
            'title' => 'No Price',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('packages', ['title' => 'No Price', 'price' => null]);
    }

    // ── Service CRUD ───────────────────────────────────────────────

    public function test_admin_can_create_service(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/services', [
            'type' => 'installation',
            'title' => 'Instalasi Baru',
            'published' => false,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('services', ['type' => 'installation', 'title' => 'Instalasi Baru']);
    }

    public function test_service_type_must_be_valid(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/services', [
            'type' => 'invalid_type',
            'title' => 'Bad Service',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    // ── Blog CRUD ──────────────────────────────────────────────────

    public function test_admin_can_create_blog_article_draft(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/blog', [
            'user_id' => $user->id,
            'title' => 'New Article',
            'body' => 'Article content',
            'status' => 'draft',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('blog_articles', [
            'title' => 'New Article',
            'status' => 'draft',
            'user_id' => $user->id,
        ]);
    }

    public function test_blog_article_requires_valid_status(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/blog', [
            'user_id' => $user->id,
            'title' => 'Bad Status',
            'body' => 'Content',
            'status' => 'invalid',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_admin_can_create_blog_category(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/blog-categories', [
            'name' => 'Kategori Baru',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('blog_categories', ['name' => 'Kategori Baru']);
    }

    public function test_admin_can_create_blog_tag(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/blog-tags', [
            'name' => 'Tag Baru',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('blog_tags', ['name' => 'Tag Baru']);
    }

    // ── FAQ CRUD ───────────────────────────────────────────────────

    public function test_admin_can_create_faq(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/faqs', [
            'question' => 'Apakah ada garansi?',
            'answer' => 'Ya, tersedia garansi sesuai ketentuan.',
            'published' => false,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('faqs', ['question' => 'Apakah ada garansi?']);
    }

    // ── Page CRUD ──────────────────────────────────────────────────

    public function test_admin_can_create_page(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/pages', [
            'title' => 'Halaman Baru',
            'body' => 'Isi halaman',
            'published' => false,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('pages', ['title' => 'Halaman Baru']);
    }

    // ── Brand CRUD ─────────────────────────────────────────────────

    public function test_admin_can_create_product_brand(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/product-brands', [
            'name' => 'Brand Baru',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('product_brands', ['name' => 'Brand Baru']);
    }

    public function test_admin_can_create_package_brand(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/package-brands', [
            'name' => 'Paket Brand Baru',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('package_brands', ['name' => 'Paket Brand Baru']);
    }

    // ── Media CRUD ─────────────────────────────────────────────────

    public function test_admin_can_create_media_asset(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/admin/media', [
            'file_path' => 'media/test.jpg',
            'disk' => 'public',
            'type' => 'image',
            'alt_text' => 'Test image',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('media_assets', ['file_path' => 'media/test.jpg']);
    }

    // ── SEO metadata CRUD ──────────────────────────────────────────

    public function test_admin_can_create_seo_metadata(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/seo', [
            'seoable_type' => $product->getMorphClass(),
            'seoable_id' => $product->id,
            'seo_title' => 'SEO Title',
            'meta_description' => 'SEO Description',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('seo_metadata', [
            'seoable_id' => $product->id,
            'seo_title' => 'SEO Title',
        ]);
    }
}
