<?php

namespace Database\Seeders;

use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Faq;
use App\Models\Page;
use App\Models\PackageBrand;
use App\Models\PackageCategory;
use App\Models\PackageUsageCategory;
use App\Models\ProductBrand;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

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
            ProductBrand::firstOrCreate(
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
            PackageBrand::firstOrCreate(
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
            Service::firstOrCreate(
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
            PackageCategory::firstOrCreate($category);
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
            PackageUsageCategory::firstOrCreate($category);
        }
    }

    protected function seedBlogContent(): void
    {
        if (BlogCategory::count() > 0) {
            return;
        }

        $user = User::first();
        if (!$user) {
            return;
        }

        $categories = [
            ['name' => 'Tips Keamanan', 'slug' => 'tips-keamanan'],
            ['name' => 'Panduan Produk', 'slug' => 'panduan-produk'],
            ['name' => 'Berita', 'slug' => 'berita'],
        ];

        foreach ($categories as $category) {
            BlogCategory::create($category);
        }

        $tags = [
            ['name' => 'CCTV', 'slug' => 'cctv'],
            ['name' => 'Keamanan', 'slug' => 'keamanan'],
            ['name' => 'Instalasi', 'slug' => 'instalasi'],
        ];

        foreach ($tags as $tag) {
            BlogTag::create($tag);
        }

        // Draft article — no published content without approval
        $article = BlogArticle::factory()->draft()->create([
            'user_id' => $user->id,
            'blog_category_id' => BlogCategory::where('slug', 'tips-keamanan')->first()?->id,
            'title' => 'Panduan Memilih Kamera CCTV untuk Rumah',
            'slug' => 'panduan-memilih-kamera-cctv-untuk-rumah',
            'excerpt' => 'Tips memilih kamera CCTV yang sesuai untuk kebutuhan rumah.',
            'body' => 'Konten artikel masih dalam tahap penyusunan dan belum dipublikasikan.',
        ]);

        $article->tags()->sync(
            BlogTag::where('slug', 'cctv')->first()?->id ? [BlogTag::where('slug', 'cctv')->first()->id] : []
        );
    }

    protected function seedFaqContent(): void
    {
        if (Faq::count() > 0) {
            return;
        }

        $faqs = [
            ['question' => 'Apakah instalasi CCTV termasuk layanan anda?', 'answer' => 'Ya, kami menyediakan layanan instalasi dan perbaikan sistem CCTV.', 'sort_order' => 1, 'published' => false],
            ['question' => 'Berapa lama proses instalasi?', 'answer' => 'Durasi instalasi tergantung pada jumlah kamera dan kompleksitas lokasi. Hubungi kami untuk estimasi.', 'sort_order' => 2, 'published' => false],
            ['question' => 'Apakah tersedia konsultasi gratis?', 'answer' => 'Ya, konsultasi awal tersedia via WhatsApp. Hubungi kami untuk detail.', 'sort_order' => 3, 'published' => false],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }
    }

    protected function seedPages(): void
    {
        if (Page::count() > 0) {
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
            Page::create($page);
        }
    }
}
