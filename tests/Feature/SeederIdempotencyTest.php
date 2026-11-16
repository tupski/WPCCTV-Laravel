<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Package;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Seeder contract: idempotent (safe to run repeatedly), complete (every
 * content type populated), and honest (published content is real text, no
 * lorem ipsum).
 */
class SeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_runs_twice_without_duplicates(): void
    {
        $this->seed(DatabaseSeeder::class);

        $counts = [
            'products' => Product::count(),
            'packages' => Package::count(),
            'services' => Service::count(),
            'articles' => BlogArticle::count(),
            'faqs' => Faq::count(),
            'pages' => Page::count(),
            'brands' => ProductBrand::count(),
            'settings' => Setting::count(),
        ];

        // Second run: must not duplicate anything and must not fail.
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($counts['products'], Product::count(), 'ProductSeeder duplicated rows');
        $this->assertSame($counts['packages'], Package::count(), 'PackageSeeder duplicated rows');
        $this->assertSame($counts['services'], Service::count(), 'ServiceSeeder duplicated rows');
        $this->assertSame($counts['articles'], BlogArticle::count(), 'BlogSeeder duplicated rows');
        $this->assertSame($counts['faqs'], Faq::count(), 'PageSeeder duplicated FAQs');
        $this->assertSame($counts['pages'], Page::count(), 'PageSeeder duplicated pages');
        $this->assertSame($counts['brands'], ProductBrand::count(), 'ProductSeeder duplicated brands');
        $this->assertSame($counts['settings'], Setting::count(), 'SettingSeeder duplicated settings');
    }

    public function test_seeder_populates_all_content_types(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(5, ProductBrand::count());
        $this->assertGreaterThanOrEqual(10, Product::count());
        $this->assertGreaterThanOrEqual(5, Package::count());
        $this->assertGreaterThanOrEqual(3, Service::count());
        $this->assertGreaterThanOrEqual(4, BlogCategory::count());
        $this->assertGreaterThanOrEqual(6, BlogArticle::query()->where('status', 'published')->count());
        $this->assertGreaterThanOrEqual(8, Faq::query()->where('published', true)->count());
        $this->assertGreaterThanOrEqual(5, Page::query()->where('published', true)->count());

        // Brand settings with the real business number.
        $this->assertSame('WP CCTV Indonesia', Setting::get('site_name'));
        $this->assertSame('6287811556995', Setting::get('whatsapp_number'));
    }

    public function test_seeded_content_is_published_and_visible_publicly(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/produk')->assertOk()->assertSee('Hikvision', false);
        $this->get('/paket-cctv')->assertOk()->assertSee('Paket CCTV Rumah', false);
        $this->get('/layanan')->assertOk()->assertSee('Instalasi', false);
        $this->get('/blog')->assertOk()->assertSee('Cara Memilih Kamera CCTV', false);
        $this->get('/faq')->assertOk()->assertSee('survei', false);
        $this->get('/halaman/tentang-kami')->assertOk()->assertSee('WP CCTV Indonesia', false);
    }

    public function test_seeded_draft_article_is_not_public(): void
    {
        $this->seed(DatabaseSeeder::class);

        $draft = BlogArticle::query()->where('status', 'draft')->first();
        $this->assertNotNull($draft, 'Seeder should include one demo draft article');

        $this->get('/blog/'.$draft->slug)->assertNotFound();
    }

    public function test_seeded_slugs_are_unique(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(
            Product::count(),
            Product::query()->distinct('slug')->count('slug'),
        );
        $this->assertSame(
            Package::count(),
            Package::query()->distinct('slug')->count('slug'),
        );
        $this->assertSame(
            Page::count(),
            Page::query()->distinct('slug')->count('slug'),
        );
    }

    public function test_seeded_articles_have_category_and_tags(): void
    {
        $this->seed(DatabaseSeeder::class);

        $published = BlogArticle::query()->where('status', 'published')->get();

        foreach ($published as $article) {
            $this->assertNotNull($article->blog_category_id, "Article [{$article->slug}] has no category");
            $this->assertGreaterThan(0, $article->tags()->count(), "Article [{$article->slug}] has no tags");
            $this->assertGreaterThanOrEqual(400, str_word_count(strip_tags($article->body)), "Article [{$article->slug}] body too short");
        }
    }

    public function test_seeded_packages_have_pivots(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (Package::all() as $package) {
            $this->assertGreaterThan(0, $package->usageCategories()->count(), "Package [{$package->slug}] has no usage category");
            $this->assertGreaterThan(0, $package->categories()->count(), "Package [{$package->slug}] has no category");
        }
    }

    public function test_seeded_content_has_no_lorem_ipsum(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ([$article = BlogArticle::pluck('body'), Page::pluck('body'), Product::pluck('description'), Package::pluck('description')] as $texts) {
            foreach ($texts as $text) {
                $this->assertStringNotContainsStringIgnoringCase('lorem', (string) $text);
                $this->assertStringNotContainsStringIgnoringCase('ipsum', (string) $text);
                $this->assertStringNotContainsStringIgnoringCase('todo', (string) $text);
                $this->assertStringNotContainsStringIgnoringCase('coming soon', (string) $text);
            }
        }
    }

    public function test_seeder_does_not_create_default_admin_credential(): void
    {
        $this->seed(DatabaseSeeder::class);

        // The only seeded user is a demo author that cannot be logged into.
        $users = User::all();
        $this->assertCount(1, $users);
        $this->assertSame('penulis@wpcctv.co.id', $users->first()->email);
        $this->assertSame(User::ROLE_CONTENT_EDITOR, $users->first()->role);
    }
}
