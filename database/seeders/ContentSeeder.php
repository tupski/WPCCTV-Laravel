<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Phase 3 seeder — superseded by the Phase 5 seeders but retained because
 * existing test suites seed through it. It only creates draft placeholders;
 * the published demo content lives in ProductSeeder, PackageSeeder,
 * ServiceSeeder, BlogSeeder and PageSeeder.
 */
class ContentSeeder extends Seeder
{
    /**
     * Seed content data: preserved brands, service types, sample drafts.
     * No default admin accounts, no invented prices/specs/warranties.
     */
    public function run(): void
    {
        $this->seedProductBrands();
        $this->seedPackageBrands();
        $this->seedServiceTypes();
        $this->seedPackageCategories();
        $this->seedPackageUsageCategories();
        $this->seedBlogContent();
        $this->seedFaqContent();
        $this->seedPages();
    }

    protected function seedProductBrands(): void
    {
        $brands = [
            'Hikvision', 'Dahua', 'HiLook', 'VIGI',
            'Tapo', 'Uniarch', 'EZVIZ', 'IMOU',
        ];

        foreach ($brands as $brand) {
            \App\Models\ProductBrand::firstOrCreate(
                ['name' => $brand],
                ['slug' => \Illuminate\Support\Str::slug($brand)]
            );
        }
    }

    protected function seedPackageBrands(): void
    {
        $brands = [
            'Hikvision', 'Dahua', 'HiLook', 'VIGI',
            'Tapo', 'Uniarch', 'EZVIZ', 'IMOU',
        ];

        foreach ($brands as $brand) {
            \App\Models\PackageBrand::firstOrCreate(
                ['name' => $brand],
                ['slug' => \Illuminate\Support\Str::slug($brand)]
            );
        }
    }

    protected function seedServiceTypes(): void
    {
        $services = [
            [
                'type' => 'installation',
                'title' => 'Layanan Instalasi CCTV',
                'description' => 'Layanan pemasangan sistem CCTV untuk rumah dan bisnis.',
                'published' => false,
            ],
            [
                'type' => 'repair',
                'title' => 'Layanan Perbaikan CCTV',
                'description' => 'Layanan perbaikan dan pemeliharaan sistem CCTV yang sudah terpasang.',
                'published' => false,
            ],
            [
                'type' => 'product_only',
                'title' => 'Pengadaan Produk CCTV',
                'description' => 'Penyediaan perangkat dan aksesori CCTV tanpa instalasi.',
                'published' => false,
            ],
        ];

        foreach ($services as $service) {
            \App\Models\Service::firstOrCreate(
                ['type' => $service['type']],
                $service + ['slug' => \Illuminate\Support\Str::slug($service['title'])]
            );
        }
    }

    protected function seedPackageCategories(): void
    {
        $categories = [
            ['name' => 'Paket Residensial', 'slug' => 'paket-residensial'],
            ['name' => 'Paket Komersial', 'slug' => 'paket-komersial'],
            ['name' => 'Paket Industri', 'slug' => 'paket-industri'],
        ];

        foreach ($categories as $category) {
            \App\Models\PackageCategory::firstOrCreate($category);
        }
    }

    protected function seedPackageUsageCategories(): void
    {
        $categories = [
            ['name' => 'Rumah Tinggal', 'slug' => 'rumah-tinggal'],
            ['name' => 'Kantor', 'slug' => 'kantor'],
            ['name' => 'Gudang', 'slug' => 'gudang'],
            ['name' => 'Toko', 'slug' => 'toko'],
            ['name' => 'Industri', 'slug' => 'industri'],
        ];

        foreach ($categories as $category) {
            \App\Models\PackageUsageCategory::firstOrCreate($category);
        }
    }

    protected function seedBlogContent(): void
    {
        if (\App\Models\BlogCategory::count() > 0) {
            return;
        }

        $user = \App\Models\User::first();
        if (!$user) {
            return;
        }

        $categories = [
            ['name' => 'Tips Keamanan', 'slug' => 'tips-keamanan'],
            ['name' => 'Panduan Produk', 'slug' => 'panduan-produk'],
            ['name' => 'Berita', 'slug' => 'berita'],
        ];

        foreach ($categories as $category) {
            \App\Models\BlogCategory::create($category);
        }

        $tags = [
            ['name' => 'CCTV', 'slug' => 'cctv'],
            ['name' => 'Keamanan', 'slug' => 'keamanan'],
            ['name' => 'Instalasi', 'slug' => 'instalasi'],
        ];

        foreach ($tags as $tag) {
            \App\Models\BlogTag::create($tag);
        }

        // Draft article — no published content without approval
        $article = \App\Models\BlogArticle::factory()->draft()->create([
            'user_id' => $user->id,
            'blog_category_id' => \App\Models\BlogCategory::where('slug', 'tips-keamanan')->first()?->id,
            'title' => 'Panduan Memilih Kamera CCTV untuk Rumah',
            'slug' => 'panduan-memilih-kamera-cctv-untuk-rumah',
            'excerpt' => 'Tips memilih kamera CCTV yang sesuai untuk kebutuhan rumah.',
            'body' => 'Konten artikel masih dalam tahap penyusunan dan belum dipublikasikan.',
        ]);

        $article->tags()->sync(
            \App\Models\BlogTag::where('slug', 'cctv')->first()?->id ? [\App\Models\BlogTag::where('slug', 'cctv')->first()->id] : []
        );
    }

    protected function seedFaqContent(): void
    {
        if (\App\Models\Faq::count() > 0) {
            return;
        }

        $faqs = [
            ['question' => 'Apakah instalasi CCTV termasuk layanan anda?', 'answer' => 'Ya, kami menyediakan layanan instalasi dan perbaikan sistem CCTV.', 'sort_order' => 1, 'published' => false],
            ['question' => 'Berapa lama proses instalasi?', 'answer' => 'Durasi instalasi tergantung pada jumlah kamera dan kompleksitas lokasi. Hubungi kami untuk estimasi.', 'sort_order' => 2, 'published' => false],
            ['question' => 'Apakah tersedia konsultasi gratis?', 'answer' => 'Ya, konsultasi awal tersedia via WhatsApp. Hubungi kami untuk detail.', 'sort_order' => 3, 'published' => false],
        ];

        foreach ($faqs as $faq) {
            \App\Models\Faq::create($faq);
        }
    }

    protected function seedPages(): void
    {
        if (\App\Models\Page::count() > 0) {
            return;
        }

        $pages = [
            [
                'title' => 'Tentang Kami',
                'slug' => 'tentang-kami',
                'body' => 'WP CCTV Indonesia adalah penyedia layanan instalasi dan perbaikan sistem CCTV.',
                'published' => false,
            ],
            [
                'title' => 'Kontak',
                'slug' => 'kontak',
                'body' => 'Hubungi kami via WhatsApp untuk konsultasi dan penawaran.',
                'published' => false,
            ],
        ];

        foreach ($pages as $page) {
            \App\Models\Page::create($page);
        }
    }
}
