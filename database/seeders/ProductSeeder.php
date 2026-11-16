<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\SeoMetadata;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Equipment brand references and the product catalog.
 *
 * Products are equipment references, not merchandise: no cart, no stock, no
 * "buy" semantics anywhere. Prices are realistic Indonesian market reference
 * ranges for equipment of this class — presented as reference information on
 * the product page, never as an online-purchase offer.
 *
 * Idempotency: brands and products are matched by slug (unique column) with
 * updateOrCreate, so re-running refreshes content in place instead of
 * duplicating rows.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $brandIds = [];

        $brands = [
            ['name' => 'Hikvision', 'description' => 'Merek kamera pengawas global dengan jangkauan produk paling lengkap, dari kamera rumahan sampai sistem korporat.'],
            ['name' => 'Dahua', 'description' => 'Merek kamera pengawas global dengan pilihan model lengkap dan harga kompetitif untuk segmen menengah.'],
            ['name' => 'Imou', 'description' => 'Anak merek Dahua untuk pasar rumahan: pemasangan mudah dan aplikasi pemantauan yang ramah pengguna.'],
            ['name' => 'Ezviz', 'description' => 'Anak merek Hikvision untuk segmen konsumen: kamera WiFi indoor dan outdoor dengan aplikasi sederhana.'],
            ['name' => 'Uniview', 'description' => 'Merek kamera pengawas dengan fokus pada solusi profesional untuk komersial dan industri.'],
            ['name' => 'HiLook', 'description' => 'Anak merek Hikvision untuk segmen harga terjangkau tanpa mengorbankan kualitas dasar.'],
        ];

        foreach ($brands as $brand) {
            $brandIds[$brand['name']] = ProductBrand::updateOrCreate(
                ['slug' => Str::slug($brand['name'])],
                ['name' => $brand['name'], 'description' => $brand['description']],
            )->id;
        }

        $products = [
            [
                'brand' => 'Hikvision',
                'name' => 'Hikvision Kamera Dome Indoor 2MP',
                'model' => 'DS-2CD1123G0-I',
                'camera_type' => 'dome',
                'description' => 'Kamera dome indoor 2 megapixel untuk pemantauan interior. Sudut pandang luas dan bentuk kompak, cocok untuk plafon toko, kantor, dan ruang kelas.',
                'key_features' => [
                    'Resolusi 2 megapixel (1080p)',
                    'Kompensasi cahaya belakang real-time',
                    'Kompresi H.265+ menghemat penyimpanan',
                    'Kamera iritasi (anti-refleksi) untuk sudut pandang stabil',
                ],
                'technical_specs' => [
                    'Resolusi: 1920 x 1080 @ 30 fps',
                    'Lens: 2.8 mm, sudut pandang horizontal 102°',
                    'IR Range: hingga 30 meter',
                    'Daya: 12 V DC / PoE',
                    'Proteksi: IP67 (tahan debu dan hujan bila dipasang outdoor)',
                ],
                'recommended_applications' => ['Toko', 'Kantor', 'Ruang kelas', 'Kasir'],
                'price_min' => 450000,
                'price_max' => 650000,
            ],
            [
                'brand' => 'Hikvision',
                'name' => 'Hikvision Kamera Bullet Outdoor 2MP',
                'model' => 'DS-2CD1023G0-I',
                'camera_type' => 'outdoor',
                'description' => 'Kamera bullet outdoor 2 megapixel dengan housing tahan cuaca. Pilihan umum untuk area parkir, pagar depan, dan garasi.',
                'key_features' => [
                    'Resolusi 2 megapixel (1080p)',
                    'Housing IP67 tahan hujan dan debu',
                    'Night vision IR hingga 30 meter',
                    'Bodi metal kuat untuk instalasi outdoor',
                ],
                'technical_specs' => [
                    'Resolusi: 1920 x 1080 @ 30 fps',
                    'Lens: 2.8 atau 3.6 mm',
                    'IR Range: hingga 30 meter',
                    'Daya: 12 V DC / PoE',
                    'Operating temperature: -30°C hingga 60°C',
                ],
                'recommended_applications' => ['Area parkir', 'Pagar depan', 'Garasi', 'Gudang'],
                'price_min' => 500000,
                'price_max' => 750000,
            ],
            [
                'brand' => 'Dahua',
                'name' => 'Dahua Kamera Full Color Outdoor 2MP',
                'model' => 'DH-HAC-HDW1239TLP-A-LED',
                'camera_type' => 'outdoor',
                'description' => 'Kamera outdoor full-color: gambar berwarna pada malam hari tanpa perlu lampu tambahan. Cocok untuk area yang butuh identifikasi warna seperti pakaian atau kendaraan.',
                'key_features' => [
                    'Full-color night vision',
                    'Resolusi 2 megapixel',
                    'Mikrofon internal untuk rekaman audio',
                    'Kompresi H.265+',
                ],
                'technical_specs' => [
                    'Resolusi: 1920 x 1080 @ 25/30 fps',
                    'Lens: 3.6 mm',
                    'Illumination distance: hingga 20 meter',
                    'Daya: 12 V DC',
                    'Proteksi: IP67',
                ],
                'recommended_applications' => ['Depan rumah', 'Area parkir', 'Jalan masuk', 'Toko'],
                'price_min' => 550000,
                'price_max' => 850000,
            ],
            [
                'brand' => 'Imou',
                'name' => 'Imou Kamera WiFi Indoor 2K',
                'model' => 'Imou Ranger 2 (A22EP)',
                'camera_type' => 'indoor',
                'description' => 'Kamera indoor WiFi 2K untuk pemantauan dalam rumah. Pemasangan tanpa kabel video, ideal untuk menyaksikan bayi, hewan peliharaan, atau ruang kerja.',
                'key_features' => [
                    'Resolusi 2K (2304 x 1296)',
                    'Koneksi WiFi, tanpa kabel video',
                    'Rotasi 355° dan kemiringan 90° dari aplikasi',
                    'Deteksi gerakan dan pelacakan otomatis',
                    'Dua arah audio',
                ],
                'technical_specs' => [
                    'Resolusi: 2304 x 1296 @ 15 fps',
                    'Konektivitas: WiFi 2.4 GHz',
                    'MicroSD: hingga 256 GB (tidak disertakan)',
                    'Daya: adaptor 5 V USB',
                    'Aplikasi: Imou Life (Android/iOS)',
                ],
                'recommended_applications' => ['Ruang keluarga', 'Kamar bayi', 'Ruang kerja', 'Ruang tamu'],
                'price_min' => 250000,
                'price_max' => 400000,
            ],
            [
                'brand' => 'Ezviz',
                'name' => 'Ezviz Kamera WiFi Outdoor 2K+',
                'model' => 'H3c 2K+',
                'camera_type' => 'outdoor',
                'description' => 'Kamera outdoor WiFi 2K+ dengan pemasangan sederhana. Cocok untuk rumah atau toko yang tidak ingin penarikan kabel video jauh.',
                'key_features' => [
                    'Resolusi 2K+ (2560 x 1440)',
                    'WiFi dual-antena untuk sinyal stabil',
                    'Deteksi manusia dengan AI',
                    'Night vision berwarna',
                ],
                'technical_specs' => [
                    'Resolusi: 2560 x 1440 @ 15 fps',
                    'Konektivitas: WiFi 2.4 GHz',
                    'MicroSD: hingga 512 GB (tidak disertakan)',
                    'Daya: adaptor 12 V DC',
                    'Proteksi: IP67',
                ],
                'recommended_applications' => ['Depan rumah', 'Toko', 'Carport', 'Halaman belakang'],
                'price_min' => 350000,
                'price_max' => 550000,
            ],
            [
                'brand' => 'Uniview',
                'name' => 'Uniview Kamera Dome Outdoor 4MP',
                'model' => 'IPC3234SB-ADZK-I0',
                'camera_type' => 'dome',
                'description' => 'Kamera dome outdoor 4 megapixel untuk detail gambar lebih tinggi. Pilihan untuk area yang butuh identifikasi wajah atau plat nomor.',
                'key_features' => [
                    'Resolusi 4 megapixel',
                    'Kompensasi cahaya 120 dB',
                    'IR hingga 30 meter',
                    'Housing vandal-resistant',
                ],
                'technical_specs' => [
                    'Resolusi: 2560 x 1440 @ 25/30 fps',
                    'Lens: 2.8 mm',
                    'IR Range: hingga 30 meter',
                    'Daya: 12 V DC / PoE',
                    'Proteksi: IP67, IK10',
                ],
                'recommended_applications' => ['Kantor', 'Pabrik', 'Sekolah', 'Area parkir'],
                'price_min' => 750000,
                'price_max' => 1100000,
            ],
            [
                'brand' => 'HiLook',
                'name' => 'HiLook Kamera Bullet Outdoor 2MP',
                'model' => 'IPC-B120H-MU',
                'camera_type' => 'bullet',
                'description' => 'Kamera bullet 2 megapixel dari HiLook, anak merek Hikvision. Pilihan hemat untuk instalasi dasar dengan kualitas terjaga.',
                'key_features' => [
                    'Resolusi 2 megapixel',
                    'IR hingga 20 meter',
                    'Housing IP67',
                    'Harga terjangkau untuk instalasi multi-titik',
                ],
                'technical_specs' => [
                    'Resolusi: 1920 x 1080 @ 30 fps',
                    'Lens: 3.6 mm',
                    'IR Range: hingga 20 meter',
                    'Daya: 12 V DC',
                    'Proteksi: IP67',
                ],
                'recommended_applications' => ['Rumah', 'Toko kecil', 'Ruko', 'Warung'],
                'price_min' => 300000,
                'price_max' => 450000,
            ],
            [
                'brand' => 'Hikvision',
                'name' => 'Hikvision NVR 8 Kanal 1.5U',
                'model' => 'DS-7608NI-K1/8P',
                'camera_type' => 'nvr',
                'description' => 'Network Video Recorder 8 kanal dengan PoE bawaan untuk sistem IP. Merekam hingga 8 kamera sekaligus dengan akses jarak jauh dari aplikasi.',
                'key_features' => [
                    '8 kanal PoE bawaan',
                    'Dukungan HDD hingga 10 TB',
                    'Akses jarak jauh via Hik-Connect',
                    'Kompresi H.265+',
                ],
                'technical_specs' => [
                    'Kanal video: 8',
                    'Port PoE: 8',
                    'HDD: 1 slot SATA (HDD tidak disertakan)',
                    'Resolusi rekaman: hingga 8 MP',
                    'Daya: AC 100-240 V',
                ],
                'recommended_applications' => ['Rumah multi-titik', 'Toko', 'Kantor', 'Gudang'],
                'price_min' => 1500000,
                'price_max' => 2100000,
            ],
            [
                'brand' => 'Dahua',
                'name' => 'Dahua DVR 4 Kanal Cooper',
                'model' => 'DH-XVR1B04H',
                'camera_type' => 'dvr',
                'description' => 'Digital Video Recorder 4 kanal untuk kamera analog HD (CVI/TVI/AHD). Pilihan untuk upgrade dari sistem CCTV analog lama.',
                'key_features' => [
                    '4 kanal analog HD',
                    'Dukungan HDD hingga 6 TB',
                    'Akses jarak jauh via iDMSS/gDMSS',
                    'Kompresi H.265+',
                ],
                'technical_specs' => [
                    'Kanal video: 4',
                    'HDD: 1 slot SATA (HDD tidak disertakan)',
                    'Rekaman: hingga 1080p Lite',
                    'Daya: 12 V DC',
                    'Format kamera: CVI / TVI / AHD / analog',
                ],
                'recommended_applications' => ['Rumah', 'Toko kecil', 'Upgrade sistem lama', 'Ruko'],
                'price_min' => 600000,
                'price_max' => 900000,
            ],
            [
                'brand' => 'HiLook',
                'name' => 'HiLook HDD CCTV Purple 2TB',
                'model' => 'WD20PURZ',
                'camera_type' => 'aksesoris',
                'description' => 'Harddisk 2 TB khusus CCTV (WD Purple) yang dirancang untuk rekaman 24/7. Perangkat penyimpanan wajib untuk NVR atau DVR.',
                'key_features' => [
                    'Dirancang untuk operasi 24/7',
                    'Optimasi tulis untuk rekaman CCTV',
                    'Kapasitas 2 TB',
                    'Toleransi beban kerja hingga 64 kamera',
                ],
                'technical_specs' => [
                    'Kapasitas: 2 TB',
                    'Form factor: 3.5 inci',
                    'Cache: 128 MB',
                    'Interface: SATA 6 Gb/s',
                    'Workload: 180 TB/tahun',
                ],
                'recommended_applications' => ['NVR', 'DVR', 'Sistem rekaman 24/7', 'Backup rekaman'],
                'price_min' => 750000,
                'price_max' => 950000,
            ],
            [
                'brand' => 'Uniview',
                'name' => 'Uniview Kamera PTZ Outdoor 4MP',
                'model' => 'IPC672LR-X4-Z-UKG',
                'camera_type' => 'ptz',
                'description' => 'Kamera PTZ 4 megapixel dengan zoom optik dan rotasi penuh. Untuk area luas yang butuh pemantauan fleksibel seperti pelataran parkir atau halaman pabrik.',
                'key_features' => [
                    'Zoom optik 4x',
                    'Rotasi horizontal 360°, vertikal 90°',
                    'Auto-tracking objek bergerak',
                    'IR hingga 100 meter',
                ],
                'technical_specs' => [
                    'Resolusi: 2560 x 1440 @ 25/30 fps',
                    'Zoom optik: 4x (2.8–12 mm)',
                    'IR Range: hingga 100 meter',
                    'Daya: PoE+ / 12 V DC',
                    'Proteksi: IP66',
                ],
                'recommended_applications' => ['Pelataran parkir', 'Pabrik', 'Perumahan cluster', 'Area luas'],
                'price_min' => 2500000,
                'price_max' => 3500000,
            ],
            [
                'brand' => 'Ezviz',
                'name' => 'Ezviz Kamera Indoor Pan & Tilt 2K',
                'model' => 'TY2 Pro 2K',
                'camera_type' => 'indoor',
                'description' => 'Kamera indoor 2K dengan rotasi otomatis mengikuti gerakan. Pilihan ekonomis untuk memantau satu ruangan penuh dari aplikasi ponsel.',
                'key_features' => [
                    'Resolusi 2K (2304 x 1296)',
                    'Rotasi otomatis mengikuti gerakan',
                    'Deteksi manusia',
                    'Dua arah audio',
                ],
                'technical_specs' => [
                    'Resolusi: 2304 x 1296 @ 15 fps',
                    'Konektivitas: WiFi 2.4 GHz',
                    'MicroSD: hingga 512 GB (tidak disertakan)',
                    'Daya: adaptor 5 V USB',
                    'Aplikasi: Ezviz (Android/iOS)',
                ],
                'recommended_applications' => ['Ruang tamu', 'Kamar bayi', 'Toko kecil', 'Ruang kerja'],
                'price_min' => 280000,
                'price_max' => 420000,
            ],
        ];

        foreach ($products as $product) {
            $model = Product::updateOrCreate(
                ['slug' => Str::slug($product['name'])],
                [
                    'brand_id' => $brandIds[$product['brand']],
                    'name' => $product['name'],
                    'model' => $product['model'],
                    'camera_type' => $product['camera_type'],
                    'description' => $product['description'],
                    'key_features' => $product['key_features'],
                    'technical_specs' => $product['technical_specs'],
                    'recommended_applications' => $product['recommended_applications'],
                    'published' => true,
                ],
            );

            SeoMetadata::updateOrCreate(
                ['seoable_type' => $model->getMorphClass(), 'seoable_id' => $model->id],
                [
                    'seo_title' => $product['name'].' | Spesifikasi & Konsultasi',
                    'meta_description' => $product['description'].' Rentang harga referensi Rp'.number_format($product['price_min'], 0, ',', '.').'–'.number_format($product['price_max'], 0, ',', '.').'.',
                    'og_type' => 'website',
                ],
            );
        }
    }
}
