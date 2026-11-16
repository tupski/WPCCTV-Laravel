<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use App\Models\Page;
use App\Models\Package;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public frontend coverage: every public page renders, draft content stays
 * hidden, and the WhatsApp CTA carries the tracked link.
 */
class PublicFrontendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The tracking service reads the WhatsApp number from settings.
        \App\Models\Setting::set('whatsapp_number', '6287811556995');
    }

    public function test_home_page_renders_with_seeded_content(): void
    {
        $package = Package::factory()->published()->create([
            'title' => 'Paket CCTV Rumah 4 Kamera',
            'camera_quantity' => 4,
            'price' => 4350000,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('WP CCTV Indonesia');
        $response->assertSee('Solusi CCTV untuk Keamanan Rumah dan Bisnis');
        $response->assertSee('Paket CCTV Rumah 4 Kamera');
        $response->assertSee('Konsultasi via WhatsApp');
        $response->assertSee('Lihat Paket CCTV');
    }

    public function test_home_page_contains_localbusiness_structured_data(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('"@type":"LocalBusiness"', false);
    }

    public function test_all_primary_public_pages_return_200(): void
    {
        Product::factory()->published()->create(['name' => 'Kamera Uji', 'slug' => 'kamera-uji']);
        Package::factory()->published()->create(['title' => 'Paket Uji', 'slug' => 'paket-uji']);
        Service::factory()->published()->create(['title' => 'Layanan Uji', 'slug' => 'layanan-uji', 'type' => 'installation']);
        Page::factory()->published()->create(['title' => 'Tentang Kami', 'slug' => 'tentang-kami']);

        $routes = [
            '/',
            '/produk',
            '/produk/kamera-uji',
            '/paket-cctv',
            '/paket-cctv/paket-uji',
            '/layanan',
            '/layanan/layanan-uji',
            '/blog',
            '/faq',
            '/kontak',
            '/halaman/tentang-kami',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertOk();
        }
    }

    public function test_products_index_filters_by_brand(): void
    {
        $brandA = \App\Models\ProductBrand::factory()->create(['name' => 'BrandA']);
        $brandB = \App\Models\ProductBrand::factory()->create(['name' => 'BrandB']);

        Product::factory()->published()->create(['name' => 'Produk A', 'brand_id' => $brandA->id]);
        Product::factory()->published()->create(['name' => 'Produk B', 'brand_id' => $brandB->id]);

        $response = $this->get('/produk?brand='.$brandA->slug);

        $response->assertOk();
        $response->assertSee('Produk A');
        $response->assertDontSee('Produk B');
    }

    public function test_product_show_renders_specs_and_cta(): void
    {
        $brand = \App\Models\ProductBrand::factory()->create(['name' => 'Hikvision']);
        $product = Product::factory()->published()->create([
            'brand_id' => $brand->id,
            'name' => 'Kamera Dome 2MP',
            'slug' => 'kamera-dome-2mp',
            'model' => 'DS-2CD1123G0-I',
            'camera_type' => 'dome',
            'description' => 'Kamera dome indoor untuk toko dan kantor.',
            'key_features' => ['Resolusi 2 megapixel', 'Kompresi H.265+'],
            'technical_specs' => ['Resolusi: 1920 x 1080', 'IR Range: 30 meter'],
            'recommended_applications' => ['Toko', 'Kantor'],
        ]);

        $response = $this->get('/produk/kamera-dome-2mp');

        $response->assertOk();
        $response->assertSee('Kamera Dome 2MP');
        $response->assertSee('DS-2CD1123G0-I');
        $response->assertSee('Resolusi 2 megapixel');
        $response->assertSee('Konsultasikan Produk Ini');
        $response->assertSee('"@type":"Product"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_product_show_returns_404_for_draft(): void
    {
        Product::factory()->create(['name' => 'Draft Kamera', 'slug' => 'draft-kamera']);

        $this->get('/produk/draft-kamera')->assertNotFound();
    }

    public function test_package_show_renders_price_and_scope(): void
    {
        $package = Package::factory()->published()->create([
            'title' => 'Paket CCTV Rumah 2 Kamera',
            'slug' => 'paket-cctv-rumah-2-kamera',
            'camera_quantity' => 2,
            'price' => 2450000,
            'included_equipment' => ['2 kamera bullet outdoor', '1 DVR 4 kanal'],
            'installation_scope' => ['Survei lokasi', 'Pemasangan 2 kamera'],
            'exclusions' => ['Ketinggian di atas 4 meter'],
        ]);

        $response = $this->get('/paket-cctv/paket-cctv-rumah-2-kamera');

        $response->assertOk();
        $response->assertSee('Paket CCTV Rumah 2 Kamera');
        $response->assertSee('Rp'.number_format(2450000, 0, ',', '.'));
        $response->assertSee('2 kamera bullet outdoor');
        $response->assertSee('Survei lokasi');
        $response->assertSee('Ketinggian di atas 4 meter');
        $response->assertSee('Konsultasikan Paket Ini');
        $response->assertSee('"@type":"Service"', false);
    }

    public function test_blog_index_and_show_render_published_articles(): void
    {
        $user = User::factory()->create();
        $category = \App\Models\BlogCategory::factory()->create(['name' => 'Tips Keamanan', 'slug' => 'tips-keamanan']);
        $tag = \App\Models\BlogTag::factory()->create(['name' => 'CCTV', 'slug' => 'cctv']);

        $article = BlogArticle::factory()->published()->create([
            'user_id' => $user->id,
            'blog_category_id' => $category->id,
            'title' => 'Cara Memilih Kamera CCTV untuk Rumah',
            'slug' => 'cara-memilih-kamera-cctv-untuk-rumah',
            'body' => 'Paragraf pertama artikel uji.',
        ]);
        $article->tags()->sync([$tag->id]);

        $index = $this->get('/blog');
        $index->assertOk();
        $index->assertSee('Cara Memilih Kamera CCTV untuk Rumah');

        $show = $this->get('/blog/cara-memilih-kamera-cctv-untuk-rumah');
        $show->assertOk();
        $show->assertSee('Cara Memilih Kamera CCTV untuk Rumah');
        $show->assertSee('"@type":"Article"', false);
        $show->assertSee('Tanya Lebih Lanjut');
    }

    public function test_blog_index_filters_by_category(): void
    {
        $user = User::factory()->create();
        $catA = \App\Models\BlogCategory::factory()->create(['name' => 'Kategori A', 'slug' => 'kategori-a']);
        $catB = \App\Models\BlogCategory::factory()->create(['name' => 'Kategori B', 'slug' => 'kategori-b']);

        BlogArticle::factory()->published()->create([
            'user_id' => $user->id,
            'blog_category_id' => $catA->id,
            'title' => 'Artikel Kategori A',
        ]);
        BlogArticle::factory()->published()->create([
            'user_id' => $user->id,
            'blog_category_id' => $catB->id,
            'title' => 'Artikel Kategori B',
        ]);

        $response = $this->get('/blog?category=kategori-a');

        $response->assertOk();
        $response->assertSee('Artikel Kategori A');
        $response->assertDontSee('Artikel Kategori B');
    }

    public function test_blog_show_returns_404_for_draft(): void
    {
        $user = User::factory()->create();
        BlogArticle::factory()->create([
            'user_id' => $user->id,
            'title' => 'Draft Artikel',
            'slug' => 'draft-artikel',
        ]);

        $this->get('/blog/draft-artikel')->assertNotFound();
    }

    public function test_faq_page_renders_published_faqs_with_structured_data(): void
    {
        \App\Models\Faq::factory()->create([
            'question' => 'Berapa lama proses instalasi CCTV?',
            'answer' => 'Instalasi rumah umumnya selesai dalam satu hari kerja.',
            'sort_order' => 1,
            'published' => true,
        ]);

        $response = $this->get('/faq');

        $response->assertOk();
        $response->assertSee('Berapa lama proses instalasi CCTV?');
        $response->assertSee('"@type":"FAQPage"', false);
    }

    public function test_contact_page_renders_form_and_info(): void
    {
        $response = $this->get('/kontak');

        $response->assertOk();
        $response->assertSee('Formulir Permintaan Konsultasi');
        $response->assertSee('name="customer_name"', false);
        $response->assertSee('name="whatsapp_number"', false);
        $response->assertSee('name="company_website"', false); // honeypot
        $response->assertSee('+62 878-1155-6995');
    }

    public function test_contact_form_creates_lead(): void
    {
        $payload = [
            'customer_name' => 'Budi Santoso',
            'whatsapp_number' => '0812-3456-7890',
            'lead_type' => 'installation',
            'requirements' => 'Butuh 4 kamera untuk rumah dua lantai.',
            'consent' => '1',
        ];

        $response = $this->post('/kontak', $payload);

        $response->assertRedirect('/kontak');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('leads', [
            'customer_name' => 'Budi Santoso',
            'whatsapp_number' => '6281234567890',
            'lead_type' => 'installation',
            'status' => 'new',
        ]);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $response = $this->from('/kontak')->post('/kontak', []);

        $response->assertRedirect('/kontak');
        $response->assertSessionHasErrors(['customer_name', 'whatsapp_number', 'requirements', 'consent']);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_contact_form_honeypot_rejects_silently(): void
    {
        $payload = [
            'customer_name' => 'Bot',
            'whatsapp_number' => '081234567890',
            'requirements' => 'Spam submission dari bot.',
            'consent' => '1',
            'company_website' => 'https://spam.example',
        ];

        $response = $this->post('/kontak', $payload);

        $response->assertRedirect('/kontak');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_whatsapp_cta_uses_tracking_endpoint(): void
    {
        $product = Product::factory()->published()->create(['name' => 'Kamera CTA', 'slug' => 'kamera-cta']);

        $response = $this->get('/produk/kamera-cta');

        $response->assertOk();
        // Blade escapes & as & in href attributes; build the expectation
        // with chr() so this file's own escaping never mangles it.
        $this->assertStringContainsString(
            '/track/whatsapp?context=product'.chr(38).'amp;slug=kamera-cta',
            (string) $response->getContent(),
        );
    }

    public function test_sitemap_lists_only_published_content(): void
    {
        $published = Product::factory()->published()->create(['slug' => 'produk-sitemap']);
        Product::factory()->create(['slug' => 'produk-draft']);
        Page::factory()->published()->create(['slug' => 'halaman-sitemap']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringContainsString(url('/produk/produk-sitemap'), $body);
        $this->assertStringContainsString(url('/halaman/halaman-sitemap'), $body);
        $this->assertStringNotContainsString('produk-draft', $body);
    }

    public function test_robots_disallows_admin_and_lists_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringContainsString('Disallow: /admin', $body);
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $body);
    }

    public function test_custom_404_page_renders(): void
    {
        $response = $this->get('/halaman/tidak-ada');

        $response->assertNotFound();
        $response->assertSee('Halaman Tidak Ditemukan');
        $response->assertSee('Kembali ke Beranda');
    }

    public function test_seeded_whatsapp_number_is_used_in_tracking_redirect(): void
    {
        $product = Product::factory()->published()->create(['name' => 'Kamera Redirect', 'slug' => 'kamera-redirect']);

        $response = $this->get('/track/whatsapp?context=product&slug=kamera-redirect');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://wa.me/6287811556995', $location);
    }
}
