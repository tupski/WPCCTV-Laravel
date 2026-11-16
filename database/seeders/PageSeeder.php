<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Page;
use App\Models\Package;
use App\Models\SeoMetadata;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Global FAQs and static pages (Tentang, Garansi, Area Layanan, Kebijakan
 * Privasi, Syarat & Ketentuan, Kontak).
 *
 * All content is published: these are the pages the public layout links to
 * from the footer, so seeding them as draft would produce broken footer
 * links. Legal texts are honest, plain-language summaries appropriate for a
 * service business — not fabricated legal boilerplate.
 *
 * Idempotency: FAQs matched by question text, pages by slug.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFaqs();
        $this->seedPages();
    }

    protected function seedFaqs(): void
    {
        $faqs = [
            ['question' => 'Apakah survei lokasi dikenakan biaya?', 'answer' => 'Survei gratis untuk area Jabodetabek. Untuk lokasi di luar itu, biaya survei disesuaikan dengan jarak dan akan disampaikan di muka sebelum jadwal disepakati.', 'sort_order' => 1],
            ['question' => 'Berapa lama proses instalasi CCTV?', 'answer' => 'Untuk rumah dengan 2–4 kamera, pemasangan umumnya selesai dalam satu hari kerja. Instalasi lebih besar (8–16 kamera) biasanya membutuhkan satu sampai dua hari tergantung panjang jalur kabel.', 'sort_order' => 2],
            ['question' => 'Apakah saya bisa melihat kamera dari ponsel?', 'answer' => 'Ya. Setiap instalasi kami sertakan setup akses jarak jauh di ponsel Anda, sehingga kamera bisa dilihat dari mana saja selama ponsel terhubung internet.', 'sort_order' => 3],
            ['question' => 'Apakah perangkat bergaransi?', 'answer' => 'Perangkat baru bergaransi resmi distributor. Garansi pemasangan (pekerjaan) mengikuti kesepakatan tertulis di penawaran — dijelaskan sebelum pengerjaan dimulai.', 'sort_order' => 4],
            ['question' => 'Apakah bisa instalasi di luar Jakarta?', 'answer' => 'Ya. Area layanan utama kami meliputi Jakarta, Bogor, Depok, Tangerang, Bekasi, dan Bandung. Lokasi lain masih bisa didiskusikan melalui WhatsApp.', 'sort_order' => 5],
            ['question' => 'Apakah CCTV bisa merekam saat listrik mati?', 'answer' => 'Bila disertakan UPS atau catu daya cadangan, ya. Tanpa cadangan daya, perekam berhenti saat listrik padam dan melanjutkan rekaman saat listrik kembali.', 'sort_order' => 6],
            ['question' => 'Berapa lama rekaman tersimpan?', 'answer' => 'Tergantung kapasitas harddisk, jumlah kamera, dan mode rekaman. Dengan harddisk 2 TB dan empat kamera mode gerakan, rekaman umumnya tersimpan dua sampai tiga bulan.', 'sort_order' => 7],
            ['question' => 'Apakah kamera CCTV bisa dipasang sendiri?', 'answer' => 'Kamera WiFi indoor bisa dipasang sendiri untuk kebutuhan sederhana. Untuk sistem multi-kamera dengan perekam, pemasangan profesional memastikan kabel rapi, konfigurasi benar, dan garansi pemasangan berlaku.', 'sort_order' => 8],
            ['question' => 'Apakah melayani perbaikan CCTV yang dipasang pihak lain?', 'answer' => 'Ya. Kami melayani diagnosis dan perbaikan sistem CCTV yang dipasang pihak lain, dengan penawaran biaya setelah pemeriksaan awal.', 'sort_order' => 9],
            ['question' => 'Bagaimana cara meminta penawaran?', 'answer' => 'Hubungi kami via WhatsApp di 0878-1155-6995, ceritakan kebutuhan Anda (jenis lokasi, perkiraan jumlah kamera), dan kami akan menjadwalkan survei atau memberi estimasi awal.', 'sort_order' => 10],
        ];

        foreach ($faqs as $faq) {
            Faq::firstOrCreate(
                ['question' => $faq['question']],
                ['answer' => $faq['answer'], 'sort_order' => $faq['sort_order'], 'published' => true],
            );
        }
    }

    protected function seedPages(): void
    {
        $pages = [
            [
                'title' => 'Tentang Kami',
                'slug' => 'tentang-kami',
                'body' => "WP CCTV Indonesia adalah penyedia jasa survei, instalasi, dan perbaikan sistem CCTV untuk rumah, toko, kantor, gudang, sekolah, dan pabrik.\n\nCara kami bekerja\n\nSetiap pekerjaan dimulai dari survei lokasi. Kami tidak memberi penawaran sebelum melihat kondisi lapangan — jumlah kamera, jenis yang tepat, dan panjang jalur kabel baru bisa ditentukan setelah kondisi lokasi diketahui.\n\nPenawaran kami tertulis dan merinci cakupan pekerjaan. Yang tidak termasuk dalam paket kami sampaikan di muka, bukan setelah pekerjaan selesai.\n\nApa yang kami jual — dan yang tidak\n\nKami menjual jasa: pemasangan dan pemeliharaan sistem pengawasan. Katalog perangkat di situs ini adalah referensi untuk membantu Anda memahami pilihan yang ada, bukan toko online. Tidak ada keranjang belanja atau pembayaran di situs ini — seluruh konsultasi dan penawaran berlangsung melalui WhatsApp.\n\nKomitmen kami\n\n- Penawaran sesuai kondisi lokasi, bukan angka tebakan\n- Cakupan pekerjaan dan pengecualian dijelaskan tertulis\n- Garansi pemasangan mengikuti kesepakatan di penawaran\n- Dukungan perawatan tersedia setelah pemasangan\n\nArea layanan\n\nKami melayani Jakarta, Bogor, Depok, Tangerang, Bekasi, dan Bandung. Lokasi lain masih bisa didiskusikan melalui WhatsApp.",
            ],
            [
                'title' => 'Garansi & Purnajual',
                'slug' => 'garansi',
                'body' => "Halaman ini menjelaskan bagaimana garansi dan dukungan purnajual bekerja di WP CCTV Indonesia.\n\nGaransi perangkat\n\nPerangkat baru yang kami pasang bergaransi resmi distributor. Durasi garansi mengikuti ketentuan masing-masing merek dan produk, umumnya satu tahun untuk kamera dan perekam. Klaim garansi perangkat dilakukan melalui jalur distributor resmi; kami bantu prosesnya.\n\nGaransi pemasangan\n\nGaransi untuk pekerjaan pemasangan (kabel, konektor, pemasangan fisik, konfigurasi) mengikuti kesepakatan tertulis di penawaran Anda. Bila ada masalah pada pekerjaan dalam masa garansi, perbaikan tidak dikenakan biaya.\n\nYang tidak termasuk garansi\n\n- Kerusakan akibat petir, banjir, atau gangguan listrik yang ekstrem\n- Kerusakan akibat pembongkaran atau pindah lokasi oleh pihak lain\n- Perubahan jaringan internet di lokasi yang membuat akses jarak jauh terputus (bisa dibantu rekonfigurasi dengan biaya kunjungan)\n\nDukungan setelah pemasangan\n\nPertanyaan penggunaan setelah serah terima bisa disampaikan melalui WhatsApp pada jam operasional. Untuk pemeriksaan berkala dan pembersihan perangkat, layanan maintenance tersedia terpisah.\n\nKlaim garansi\n\nSampaikan melalui WhatsApp dengan menyebutkan tanggal pemasangan dan gejala yang muncul. Kami diagnosis dulu sebelum menentukan apakah penanganan melalui garansi pemasangan atau garansi distributor perangkat.",
            ],
            [
                'title' => 'Area Layanan',
                'slug' => 'area-layanan',
                'body' => "Kami melayani pemasangan dan perbaikan CCTV di wilayah berikut:\n\n- Jakarta (seluruh wilayah)\n- Bogor\n- Depok\n- Tangerang\n- Bekasi\n- Bandung\n\nSurvei lokasi gratis untuk area Jabodetabek. Untuk Bandung dan lokasi lain, biaya survei menyesuaikan jarak dan disampaikan di muka sebelum jadwal disepakati.\n\nLokasi di luar daftar\n\nLokasi di luar daftar masih bisa didiskusikan — terutama untuk proyek dengan jumlah titik kamera yang besar. Hubungi kami melalui WhatsApp dengan menyebutkan lokasi dan perkiraan kebutuhan Anda.\n\nWaktu respons\n\nPermintaan survei di area Jabodetabek biasanya terjadwal dalam dua sampai tiga hari kerja. Pekerjaan instalasi menyusul setelah penawaran disetujui.",
            ],
            [
                'title' => 'Kebijakan Privasi',
                'slug' => 'kebijakan-privasi',
                'body' => "Kebijakan ini menjelaskan data apa yang dikumpulkan situs WP CCTV Indonesia dan bagaimana data itu digunakan.\n\nData yang kami kumpulkan\n\n1. Data formulir kontak. Bila Anda mengisi formulir konsultasi, kami menyimpan nama, nomor WhatsApp, jenis kebutuhan, dan uraian kebutuhan Anda. Data ini hanya digunakan untuk menindaklanjuti permintaan Anda.\n\n2. Data kunjungan anonim. Situs ini mencatat kunjungan halaman secara anonim: halaman yang dilihat, waktu kunjungan, jenis perangkat, dan domain perujuk. Kami tidak menyimpan alamat IP penuh, tidak memasang cookie iklan, dan tidak menjual data kunjungan.\n\n3. Data klik WhatsApp. Bila Anda menekan tombol WhatsApp, kami mencatat bahwa klik terjadi (halaman asal, waktu, jenis perangkat). Pencatatan ini tidak berarti pesan terkirim atau percakapan berlangsung — itu terjadi sepenuhnya di WhatsApp.\n\nPenghormatan Do Not Track\n\nBila peramban Anda mengirim sinyal Do Not Track atau Global Privacy Control, pencatatan kunjungan Anda dihentikan sepenuhnya.\n\nCookie yang digunakan\n\nSitus ini hanya memakai cookie teknis: sesi (untuk bagian admin) dan satu cookie anonim untuk membedakan kunjungan berulang dalam analitik. Tidak ada cookie iklan atau pelacak pihak ketiga.\n\nBerapa lama data disimpan\n\nData kunjungan anonim dan catatan klik dihapus secara otomatis sesuai kebijakan retensi internal. Data lead (nama dan nomor WhatsApp dari formulir) disimpan selama diperlukan untuk tindak lanjut dan dapat dihapus atas permintaan Anda.\n\nPermintaan penghapusan\n\nUntuk meminta penghapusan data yang Anda tinggalkan melalui formulir, hubungi kami melalui WhatsApp dengan menyebutkan nama dan nomor yang pernah dikirim.\n\nPerubahan kebijakan\n\nPerubahan kebijakan ini akan dipublikasikan di halaman ini dengan tanggal pembaruan.",
            ],
            [
                'title' => 'Syarat & Ketentuan',
                'slug' => 'syarat-ketentuan',
                'body' => "Dengan menggunakan situs WP CCTV Indonesia, Anda menyetujui ketentuan berikut.\n\nSifat informasi di situs ini\n\nKatalog produk di situs ini adalah referensi perangkat, bukan penawaran jual online. Harga yang tercantum pada halaman paket adalah harga awal yang dapat menyesuaikan setelah survei lokasi. Penawaran final selalu diberikan secara tertulis melalui WhatsApp.\n\nTidak ada transaksi online\n\nSitus ini tidak menyediakan fitur pembelian, pembayaran, atau pemesanan online. Seluruh kesepakatan bisnis berlangsung melalui komunikasi langsung dengan tim kami.\n\nAkurasi konten\n\nKami berupaya menjaga spesifikasi dan informasi tetap akurat, namun spesifikasi perangkat dapat berubah oleh pabrikan tanpa pemberitahuan. Untuk keputusan pembelian perangkat, konfirmasi spesifikasi terkini kepada tim kami.\n\nArtikel blog\n\nKonten blog disusun untuk tujuan edukasi umum dan bukan pengganti konsultasi teknis untuk kondisi lokasi spesifik Anda.\n\nTautan pihak ketiga\n\nSitus ini dapat memuat tautan ke situs pihak ketiga (misalnya WhatsApp). Kami tidak bertanggung jawab atas isi atau kebijakan situs pihak ketiga tersebut.\n\nHak kekayaan intelektual\n\nNama merek produk yang disebutkan adalah milik pemiliknya masing-masing dan digunakan hanya untuk identifikasi perangkat yang kami pasang.\n\nPerubahan ketentuan\n\nKetentuan ini dapat diperbarui sewaktu-waktu; versi terbaru selalu tersedia di halaman ini.",
            ],
        ];

        foreach ($pages as $page) {
            $model = Page::updateOrCreate(
                ['slug' => $page['slug']],
                ['title' => $page['title'], 'body' => $page['body'], 'published' => true],
            );

            SeoMetadata::updateOrCreate(
                ['seoable_type' => $model->getMorphClass(), 'seoable_id' => $model->id],
                [
                    'seo_title' => $page['title'].' | WP CCTV Indonesia',
                    'meta_description' => Str::limit(strip_tags($page['body']), 155),
                    'og_type' => 'website',
                ],
            );
        }
    }
}
