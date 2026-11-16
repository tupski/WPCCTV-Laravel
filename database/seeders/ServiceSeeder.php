<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Service page content.
 *
 * Scope, applications and process describe verified operational reality —
 * what a CCTV installation/repair crew actually does. Deliberately no invented
 * warranty durations or "kepuasan 100%" claims.
 *
 * Idempotency: matched by `type` (one row per type by design).
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'type' => 'installation',
                'title' => 'Instalasi CCTV',
                'description' => 'Pemasangan sistem CCTV lengkap untuk rumah, toko, kantor, gudang, sekolah, dan pabrik — dari survei lokasi sampai sistem berjalan dan bisa dilihat dari ponsel Anda.',
                'scope' => [
                    'Survei lokasi dan analisis titik buta',
                    'Rekomendasi jenis dan jumlah kamera',
                    'Pemasangan kamera dan penarikan kabel',
                    'Konfigurasi perekam (DVR/NVR) dan pengaturan rekaman',
                    'Setup akses jarak jauh di ponsel',
                    'Pengujian sistem dan serah terima',
                ],
                'applications' => ['Rumah tinggal', 'Toko dan ruko', 'Kantor', 'Gudang', 'Sekolah', 'Pabrik'],
                'process' => [
                    'Konsultasi kebutuhan via WhatsApp',
                    'Survei lokasi (gratis untuk area Jabodetabek)',
                    'Penawaran tertulis dengan rincian pekerjaan',
                    'Pemasangan sesuai jadwal yang disepakati',
                    'Pengujian sistem dan panduan penggunaan',
                ],
                'published' => true,
            ],
            [
                'type' => 'repair',
                'title' => 'Servis & Perbaikan CCTV',
                'description' => 'Diagnosis dan perbaikan sistem CCTV yang sudah terpasang: kamera mati, gambar buram, rekaman hilang, atau tidak bisa diakses dari ponsel.',
                'scope' => [
                    'Pemeriksaan kamera dan jalur kabel',
                    'Diagnosis perekam dan penyimpanan',
                    'Perbaikan atau penggantian komponen rusak',
                    'Rekonfigurasi sistem dan akses jarak jauh',
                    'Pembersihan lensa dan housing',
                ],
                'applications' => ['Sistem CCTV rumah', 'Sistem CCTV toko', 'Sistem CCTV kantor', 'Sistem CCTV gudang'],
                'process' => [
                    'Ceritakan gejalanya via WhatsApp',
                    'Diagnosis awal (dapat dilakukan dari foto/video bila memungkinkan)',
                    'Kunjungan teknisi bila diperlukan',
                    'Penawaran biaya perbaikan sebelum dikerjakan',
                    'Perbaikan dan pengujian ulang',
                ],
                'published' => true,
            ],
            [
                'type' => 'product_only',
                'title' => 'Pengadaan Perangkat CCTV',
                'description' => 'Penyediaan perangkat CCTV tanpa jasa instalasi untuk kontraktor atau instalator lain yang sudah punya tim pemasangan sendiri.',
                'scope' => [
                    'Rekomendasi spesifikasi sesuai kebutuhan proyek',
                    'Pengadaan kamera, perekam, dan aksesori',
                    'Garansi resmi distributor untuk perangkat baru',
                ],
                'applications' => ['Kontraktor', 'Instalator', 'Proyek IT', 'Pengadaan kantor'],
                'process' => [
                    'Sampaikan daftar kebutuhan via WhatsApp',
                    'Rekomendasi dan penawaran harga perangkat',
                    'Konfirmasi ketersediaan stok',
                    'Pengiriman atau pengambilan langsung',
                ],
                'published' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['type' => $service['type']],
                [
                    'title' => $service['title'],
                    'slug' => Str::slug($service['title']),
                    'description' => $service['description'],
                    'scope' => $service['scope'],
                    'applications' => $service['applications'],
                    'process' => $service['process'],
                    'published' => true,
                ],
            );
        }
    }
}
