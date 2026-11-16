<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageBrand;
use App\Models\PackageCategory;
use App\Models\PackageUsageCategory;
use App\Models\SeoMetadata;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Installation package offers — the primary commercial catalog.
 *
 * Prices are realistic reference points for the Indonesian CCTV installation
 * market, scoped to what the package includes. They are starting points that
 * adjust after a site survey; the product page says so explicitly. Nothing is
 * presented as purchasable online.
 *
 * Idempotency: packages matched by slug, categories by slug, pivots via sync.
 */
class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBrands();
        $this->seedCategories();
        $this->seedPackages();
    }

    protected function seedBrands(): void
    {
        $brands = [
            ['name' => 'Hikvision', 'description' => 'Paket dengan perangkat Hikvision untuk segmen standar hingga profesional.'],
            ['name' => 'HiLook', 'description' => 'Paket hemat dengan perangkat HiLook untuk kebutuhan dasar.'],
            ['name' => 'Dahua', 'description' => 'Paket dengan perangkat Dahua, kaya fitur dengan harga kompetitif.'],
        ];

        foreach ($brands as $brand) {
            PackageBrand::updateOrCreate(
                ['slug' => Str::slug($brand['name'])],
                ['name' => $brand['name'], 'description' => $brand['description']],
            );
        }
    }

    protected function seedCategories(): void
    {
        PackageCategory::firstOrCreate(['slug' => 'paket-residensial'], ['name' => 'Paket Residensial']);
        PackageCategory::firstOrCreate(['slug' => 'paket-komersial'], ['name' => 'Paket Komersial']);
        PackageCategory::firstOrCreate(['slug' => 'paket-industri'], ['name' => 'Paket Industri']);

        PackageUsageCategory::firstOrCreate(['slug' => 'rumah-tinggal'], ['name' => 'Rumah Tinggal']);
        PackageUsageCategory::firstOrCreate(['slug' => 'toko'], ['name' => 'Toko']);
        PackageUsageCategory::firstOrCreate(['slug' => 'kantor'], ['name' => 'Kantor']);
        PackageUsageCategory::firstOrCreate(['slug' => 'gudang'], ['name' => 'Gudang']);
        PackageUsageCategory::firstOrCreate(['slug' => 'sekolah'], ['name' => 'Sekolah']);
        PackageUsageCategory::firstOrCreate(['slug' => 'pabrik'], ['name' => 'Pabrik']);
    }

    protected function seedPackages(): void
    {
        $brandHikvision = PackageBrand::where('slug', 'hikvision')->first()?->id;
        $brandHilook = PackageBrand::where('slug', 'hilook')->first()?->id;
        $brandDahua = PackageBrand::where('slug', 'dahua')->first()?->id;

        $categories = [
            'residensial' => PackageCategory::where('slug', 'paket-residensial')->first()?->id,
            'komersial' => PackageCategory::where('slug', 'paket-komersial')->first()?->id,
            'industri' => PackageCategory::where('slug', 'paket-industri')->first()?->id,
        ];

        $usage = [
            'rumah' => PackageUsageCategory::where('slug', 'rumah-tinggal')->first()?->id,
            'toko' => PackageUsageCategory::where('slug', 'toko')->first()?->id,
            'kantor' => PackageUsageCategory::where('slug', 'kantor')->first()?->id,
            'gudang' => PackageUsageCategory::where('slug', 'gudang')->first()?->id,
            'sekolah' => PackageUsageCategory::where('slug', 'sekolah')->first()?->id,
            'pabrik' => PackageUsageCategory::where('slug', 'pabrik')->first()?->id,
        ];

        $packages = [
            [
                'title' => 'Paket CCTV Rumah 2 Kamera',
                'brand_id' => $brandHilook,
                'category' => 'residensial',
                'usage' => ['rumah'],
                'camera_quantity' => 2,
                'price' => 2450000,
                'description' => 'Paket awal untuk rumah tinggal: dua titik kamera untuk area paling penting seperti depan rumah dan ruang keluarga. Pemasangan selesai umumnya dalam satu hari.',
                'included_equipment' => [
                    '2 kamera bullet outdoor 2MP HiLook',
                    '1 DVR 4 kanal HiLook',
                    '1 HDD 1 TB khusus CCTV',
                    'Kabel, konektor, dan penyangga sesuai kebutuhan',
                    'Adaptor dan stabilizer daya',
                ],
                'installation_scope' => [
                    'Survei lokasi dan penentuan titik kamera',
                    'Pemasangan 2 kamera (maksimal 1 lantai)',
                    'Penarikan kabel rapi sepanjang jalur tersedia',
                    'Konfigurasi DVR dan pengaturan rekaman',
                    'Setup akses jarak jauh di ponsel Anda',
                    'Pengujian sistem dan panduan singkat penggunaan',
                ],
                'exclusions' => [
                    'Penarikan kabel melalui plafon gypsum yang memerlukan pembongkaran khusus',
                    'Ketinggian instalasi di atas 4 meter',
                    'Perangkat tambahan di luar daftar (dihitung terpisah)',
                ],
                'installation_process' => [
                    'Konsultasi dan survei lokasi',
                    'Penawaran final sesuai kondisi rumah',
                    'Pemasangan kamera dan penarikan kabel',
                    'Konfigurasi DVR dan pengujian rekaman',
                    'Setup aplikasi ponsel dan serah terima',
                ],
            ],
            [
                'title' => 'Paket CCTV Rumah 4 Kamera',
                'brand_id' => $brandHikvision,
                'category' => 'residensial',
                'usage' => ['rumah'],
                'camera_quantity' => 4,
                'price' => 4350000,
                'description' => 'Paket paling diminati untuk rumah: empat titik kamera mencakup depan, belakang, carport, dan ruang utama. Rekaman tersimpan 2–4 minggu tergantung pengaturan.',
                'included_equipment' => [
                    '4 kamera outdoor/indoor 2MP Hikvision',
                    '1 NVR 8 kanal dengan PoE Hikvision',
                    '1 HDD 2 TB khusus CCTV',
                    'Kabel UTP, konektor, dan penyangga sesuai kebutuhan',
                    'PoE switch bila diperlukan',
                ],
                'installation_scope' => [
                    'Survei lokasi dan rekomendasi titik kamera',
                    'Pemasangan 4 kamera (maksimal 2 lantai)',
                    'Penarikan kabel UTP rapi',
                    'Konfigurasi NVR dan pengaturan rekaman',
                    'Setup akses jarak jauh di ponsel Anda',
                    'Pengujian seluruh kanal dan panduan penggunaan',
                ],
                'exclusions' => [
                    'Pembongkaran plafon gypsum besar-besaran',
                    'Ketinggian instalasi di atas 4 meter',
                    'Perangkat tambahan di luar daftar (dihitung terpisah)',
                ],
                'installation_process' => [
                    'Konsultasi dan survei lokasi',
                    'Penawaran final sesuai kondisi rumah',
                    'Pemasangan kamera dan penarikan kabel',
                    'Konfigurasi NVR dan pengujian rekaman',
                    'Setup aplikasi ponsel dan serah terima',
                ],
            ],
            [
                'title' => 'Paket CCTV Toko 4 Kamera',
                'brand_id' => $brandDahua,
                'category' => 'komersial',
                'usage' => ['toko'],
                'camera_quantity' => 4,
                'price' => 4950000,
                'description' => 'Paket untuk toko dan ruko: fokus pada kasir, pintu masuk, dan area rak. Satu kamera full-color untuk identifikasi detail pada malam hari.',
                'included_equipment' => [
                    '3 kamera dome 2MP Dahua (indoor)',
                    '1 kamera full-color outdoor 2MP Dahua',
                    '1 DVR/XVR 4 kanal Dahua',
                    '1 HDD 2 TB khusus CCTV',
                    'Kabel, konektor, dan material pemasangan',
                ],
                'installation_scope' => [
                    'Survei toko dan pemetaan area rawan',
                    'Pemasangan 4 kamera sesuai hasil survei',
                    'Penarikan kabel rapi sepanjang jalur tersedia',
                    'Konfigurasi perekam dan pengaturan rekaman',
                    'Setup akses jarak jauh untuk pemilik',
                    'Pelatihan singkat penggunaan untuk staf',
                ],
                'exclusions' => [
                    'Integrasi dengan sistem POS/kasir',
                    'Ketinggian instalasi di atas 4 meter',
                    'Perangkat tambahan di luar daftar (dihitung terpisah)',
                ],
                'installation_process' => [
                    'Konsultasi dan survei toko',
                    'Penawaran final sesuai kondisi lokasi',
                    'Pemasangan di luar jam operasional bila diminta',
                    'Konfigurasi perekam dan pengujian',
                    'Pelatihan staf dan serah terima',
                ],
            ],
            [
                'title' => 'Paket CCTV Kantor 8 Kamera',
                'brand_id' => $brandHikvision,
                'category' => 'komersial',
                'usage' => ['kantor'],
                'camera_quantity' => 8,
                'price' => 9250000,
                'description' => 'Paket untuk kantor lantai tunggal: lobi, koridor, ruang kerja, dan area parkir. Sistem IP dengan PoE sehingga penarikan kabel lebih sederhana.',
                'included_equipment' => [
                    '6 kamera dome 2MP Hikvision (indoor)',
                    '2 kamera bullet 2MP Hikvision (outdoor)',
                    '1 NVR 8 kanal PoE Hikvision',
                    '1 HDD 4 TB khusus CCTV',
                    'Kabel UTP dan material pemasangan',
                ],
                'installation_scope' => [
                    'Survei kantor dan pemetaan area',
                    'Pemasangan 8 kamera sesuai hasil survei',
                    'Penarikan kabel UTP rapi',
                    'Konfigurasi NVR dan pengaturan rekaman',
                    'Setup akses jarak jauh untuk admin',
                    'Dokumentasi titik kamera dan akun akses',
                ],
                'exclusions' => [
                    'Integrasi kontrol akses (access door)',
                    'Ketinggian instalasi di atas 4 meter',
                    'Perangkat tambahan di luar daftar (dihitung terpisah)',
                ],
                'installation_process' => [
                    'Konsultasi dan survei kantor',
                    'Penawaran final sesuai kondisi lokasi',
                    'Pemasangan di luar jam kerja bila diminta',
                    'Konfigurasi NVR dan pengujian',
                    'Dokumentasi dan serah terima',
                ],
            ],
            [
                'title' => 'Paket CCTV Gudang 8 Kamera',
                'brand_id' => $brandDahua,
                'category' => 'industri',
                'usage' => ['gudang'],
                'camera_quantity' => 8,
                'price' => 10500000,
                'description' => 'Paket untuk gudang dan area industri: kamera resolusi tinggi untuk area luas, plus satu kamera PTZ untuk pemantauan fleksibel.',
                'included_equipment' => [
                    '7 kamera bullet 2MP Dahua (outdoor/indoor)',
                    '1 kamera PTZ 4MP Dahua',
                    '1 NVR 8 kanal Dahua',
                    '1 HDD 4 TB khusus CCTV',
                    'Kabel dan material pemasangan untuk area luas',
                ],
                'installation_scope' => [
                    'Survei gudang dan pemetaan jalur kabel',
                    'Pemasangan 8 kamera termasuk 1 PTZ',
                    'Penarikan kabel jalur tray atau conduit',
                    'Konfigurasi NVR dan pengaturan rekaman',
                    'Setup akses jarak jauh untuk pengelola',
                    'Pengujian seluruh kanal dan serah terima',
                ],
                'exclusions' => [
                    'Pembangunan jalur tray/conduit baru',
                    'Ketinggian instalasi di atas 6 meter',
                    'Perangkat tambahan di luar daftar (dihitung terpisah)',
                ],
                'installation_process' => [
                    'Konsultasi dan survei gudang',
                    'Penawaran final sesuai kondisi lokasi',
                    'Pemasangan dan penarikan kabel',
                    'Konfigurasi NVR dan pengujian',
                    'Dokumentasi dan serah terima',
                ],
            ],
            [
                'title' => 'Paket CCTV Sekolah 16 Kamera',
                'brand_id' => $brandHikvision,
                'category' => 'komersial',
                'usage' => ['sekolah'],
                'camera_quantity' => 16,
                'price' => 17500000,
                'description' => 'Paket untuk sekolah dan kampus: koridor, gerbang, kantin, dan area parkir. Rekaman terpusat dengan akses berjenjang untuk pengelola.',
                'included_equipment' => [
                    '14 kamera dome 2MP Hikvision (indoor)',
                    '2 kamera bullet 2MP Hikvision (outdoor)',
                    '1 NVR 16 kanal PoE Hikvision',
                    '1 HDD 6 TB khusus CCTV',
                    'Kabel UTP dan material pemasangan',
                ],
                'installation_scope' => [
                    'Survei lokasi dan pemetaan area sekolah',
                    'Pemasangan 16 kamera sesuai hasil survei',
                    'Penarikan kabel UTP rapi',
                    'Konfigurasi NVR dan pengaturan rekaman',
                    'Setup akses jarak jauh untuk pengelola',
                    'Pelatihan operator dan serah terima',
                ],
                'exclusions' => [
                    'Integrasi dengan sistem kehadiran siswa',
                    'Ketinggian instalasi di atas 4 meter',
                    'Perangkat tambahan di luar daftar (dihitung terpisah)',
                ],
                'installation_process' => [
                    'Konsultasi dan survei sekolah',
                    'Penawaran final sesuai kondisi lokasi',
                    'Pemasangan di luar jam belajar bila diminta',
                    'Konfigurasi NVR dan pengujian',
                    'Pelatihan operator dan serah terima',
                ],
            ],
        ];

        foreach ($packages as $package) {
            $model = Package::updateOrCreate(
                ['slug' => Str::slug($package['title'])],
                [
                    'brand_id' => $package['brand_id'],
                    'title' => $package['title'],
                    'description' => $package['description'],
                    'camera_quantity' => $package['camera_quantity'],
                    'included_equipment' => $package['included_equipment'],
                    'installation_scope' => $package['installation_scope'],
                    'installation_process' => $package['installation_process'],
                    'exclusions' => $package['exclusions'],
                    'relevant_specs' => null,
                    'price' => $package['price'],
                    'published' => true,
                ],
            );

            if ($package['category'] !== null) {
                $model->categories()->sync(array_filter([$categories[$package['category']]]));
            }

            $model->usageCategories()->sync(array_filter($package['usage'] !== [] ? array_map(fn ($key) => $usage[$key], $package['usage']) : []));

            SeoMetadata::updateOrCreate(
                ['seoable_type' => $model->getMorphClass(), 'seoable_id' => $model->id],
                [
                    'seo_title' => $package['title'].' | Harga & Cakupan',
                    'meta_description' => $package['description'].' Mulai Rp'.number_format($package['price'], 0, ',', '.').'. Konsultasikan paket ini via WhatsApp.',
                    'og_type' => 'website',
                ],
            );
        }
    }
}
