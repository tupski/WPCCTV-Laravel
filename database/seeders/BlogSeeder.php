<?php

namespace Database\Seeders;

use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\SeoMetadata;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Blog categories, tags and informative articles.
 *
 * Articles are 400–700 words of genuine Indonesian educational content about
 * CCTV selection, installation and maintenance — not filler. Each is linked to
 * a relevant package where one exists, so internal links connect content to
 * the commercial offer (PRD §6.4).
 *
 * Idempotency: categories/tags matched by slug, articles by slug, tag pivots
 * via sync. Re-running refreshes content in place.
 */
class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('email', UserSeeder::DEMO_AUTHOR_EMAIL)->first();

        if ($author === null) {
            report(new \RuntimeException('BlogSeeder dilewati: akun penulis demo tidak ditemukan. Jalankan UserSeeder lebih dulu.'));

            return;
        }

        $categories = [
            ['name' => 'Tips Keamanan', 'slug' => 'tips-keamanan', 'description' => 'Praktik menjaga keamanan rumah dan bisnis sehari-hari.'],
            ['name' => 'Panduan Memilih CCTV', 'slug' => 'panduan-memilih-cctv', 'description' => 'Cara memilih kamera, perekam, dan aksesori sesuai kebutuhan.'],
            ['name' => 'Instalasi & Perawatan', 'slug' => 'instalasi-perawatan', 'description' => 'Proses pemasangan, perawatan berkala, dan troubleshooting.'],
            ['name' => 'Teknologi CCTV', 'slug' => 'teknologi-cctv', 'description' => 'Penjelasan teknologi kamera pengawas dengan bahasa sederhana.'],
        ];

        foreach ($categories as $category) {
            BlogCategory::firstOrCreate(['slug' => $category['slug']], ['name' => $category['name'], 'description' => $category['description']]);
        }

        $tags = [
            ['name' => 'CCTV', 'slug' => 'cctv'],
            ['name' => 'Keamanan Rumah', 'slug' => 'keamanan-rumah'],
            ['name' => 'Instalasi', 'slug' => 'instalasi'],
            ['name' => 'Kamera Outdoor', 'slug' => 'kamera-outdoor'],
            ['name' => 'Kamera Indoor', 'slug' => 'kamera-indoor'],
            ['name' => 'Perawatan', 'slug' => 'perawatan'],
        ];

        foreach ($tags as $tag) {
            BlogTag::firstOrCreate(['slug' => $tag['slug']], ['name' => $tag['name']]);
        }

        $catId = fn (string $slug): ?int => BlogCategory::where('slug', $slug)->first()?->id;
        $tagIds = fn (array $slugs): array => BlogTag::whereIn('slug', $slugs)->pluck('id')->all();

        $articles = [
            [
                'title' => 'Cara Memilih Kamera CCTV untuk Rumah: Panduan Praktis',
                'category' => 'panduan-memilih-cctv',
                'tags' => ['cctv', 'keamanan-rumah', 'kamera-outdoor'],
                'excerpt' => 'Jumlah titik, jenis kamera, dan kapasitas penyimpanan — tiga keputusan utama sebelum memasang CCTV di rumah. Panduan singkat tanpa istilah teknis berlebihan.',
                'body' => "Memasang CCTV di rumah bukan sekadar membeli kamera lalu memasangnya di dinding. Keputusan yang tepat di awal membuat sistem benar-benar berguna saat dibutuhkan, dan menghemat biaya yang tidak perlu.\n\nTentukan dulu apa yang ingin diawasi\n\nMulai dari titik-titik yang paling penting: pintu masuk utama, carport atau garasi, dan area belakang rumah. Untuk rumah satu lantai, dua sampai empat kamera biasanya cukup. Rumah dua lantai atau dengan halaman luas mungkin butuh lebih banyak.\n\nJangan tergoda memasang kamera di setiap sudut. Terlalu banyak titik justru membuat pemantauan melelahkan dan biaya membengkak. Kamera yang mengawasi area yang sama dua kali adalah pemborosan yang sering terjadi tanpa disadari.\n\nPilih jenis kamera sesuai lokasi\n\nKamera outdoor harus punya bodi tahan hujan (minimal berstandar IP66 atau IP67) dan night vision yang memadai. Kamera indoor bisa memakai model WiFi yang lebih mudah dipasang, tapi tetap perlu sumber daya listrik di dekatnya.\n\nUntuk identifikasi detail seperti wajah atau plat nomor, pilih kamera dengan resolusi minimal 2 megapixel dan posisi pemasangan yang tepat — kamera yang terlalu tinggi hanya akan merekam atap kepala. Resolusi tinggi tidak menolong bila sudut pemasangannya salah.\n\nHitung kebutuhan penyimpanan\n\nKapasitas penyimpanan menentukan berapa lama rekaman tersimpan. Sebagai gambaran kasar, empat kamera 2 megapixel dengan rekaman terus-menerus membutuhkan harddisk sekitar 2 TB untuk rekaman dua sampai tiga minggu.\n\nBanyak sistem modern memakai mode rekaman berbasis gerakan, yang bisa memperpanjang durasi penyimpanan dua sampai tiga kali lipat. Mode ini juga memudahkan pencarian: Anda tidak perlu memutar rekaman kosong berjam-jam untuk menemukan kejadian sepuluh detik.\n\nJangan lupakan akses jarak jauh\n\nSalah satu manfaat terbesar CCTV modern adalah bisa melihat rumah dari mana saja lewat aplikasi ponsel. Pastikan sistem yang dipilih punya aplikasi resmi yang stabil, dan minta teknisi mengaturnya saat instalasi selesai. Cek juga apakah aplikasi itu mendukung notifikasi gerakan — fitur yang berguna untuk tahu ada aktivitas di rumah saat Anda sedang tidak di tempat.\n\nPerhatikan kualitas night vision\n\nRekaman malam hari justru yang paling sering dibutuhkan, karena itulah jam rawan. Kamera dengan night vision inframerah standar menghasilkan gambar hitam-putih; model full-color menghasilkan gambar berwarna dalam kondisi minim cahaya, yang membantu identifikasi warna pakaian atau kendaraan.\n\nSoal anggaran\n\nTotal biaya bukan hanya kamera. Perekam, harddisk, kabel, dan jasa pemasangan semuanya masuk hitungan. Paket instalasi yang jelas cakupannya biasanya lebih mudah dianggarkan daripada membeli komponen terpisah — dan memberi satu pihak yang bertanggung jawab bila ada masalah.\n\nTerakhir, konsultasikan sebelum membeli\n\nSetiap rumah punya kondisi berbeda — lebar halaman, material dinding, ketersediaan colokan, dan kekuatan sinyal WiFi di tiap sudut. Konsultasi singkat sebelum membeli biasanya menghemat biaya dan mencegah pemasangan yang tidak efektif. Ceritakan kebutuhan Anda, biarkan spesifikasi mengikuti kondisi lapangan — bukan sebaliknya.",
                'reading_time' => 6,
                'package_slug' => 'paket-cctv-rumah-4-kamera',
            ],
            [
                'title' => 'Kamera CCTV Indoor vs Outdoor: Apa Bedanya dan Kapan Dipakai',
                'category' => 'panduan-memilih-cctv',
                'tags' => ['cctv', 'kamera-indoor', 'kamera-outdoor'],
                'excerpt' => 'Kamera indoor dan outdoor dibuat untuk kondisi berbeda. Memasang kamera indoor di luar ruangan adalah kesalahan umum yang berujung pada kamera mati dalam hitungan bulan.',
                'body' => "Perbedaan kamera indoor dan outdoor bukan sekadar bentuk. Keduanya dirancang untuk kondisi lingkungan yang sangat berbeda, dan menukarnya hampir selalu berujung pada masalah.\n\nKamera outdoor: dibangun untuk cuaca\n\nKamera outdoor harus tahan hujan, debu, dan perubahan suhu ekstrem. Standar proteksi yang umum adalah IP66 atau IP67 — artinya bodi kamera tertutup rapat terhadap semburan air dan debu. Di iklim tropis seperti Indonesia, kelembapan tinggi adalah tantangan sehari-hari, bukan kondisi luar biasa.\n\nKamera bullet adalah bentuk yang paling umum untuk outdoor: silinder memanjang dengan pelindung mata kamera, mudah diarahkan, dan biasanya punya jangkauan night vision lebih jauh. Bentuk ini juga membuat burung dan serangga sulit bertengger tepat di depan lensa.\n\nKamera indoor: fokus pada sudut pandang\n\nKamera indoor tidak butuh proteksi cuaca, jadi bentuknya bisa lebih kecil dan estetis. Model dome sangat umum di toko dan kantor karena sudut pandangnya luas dan tidak mudah dibobol posisinya.\n\nUntuk rumah, kamera indoor WiFi seperti model pan-tilt populer karena bisa diputar dari ponsel untuk memantau satu ruangan penuh. Model ini biasanya juga punya mikrofon dan speaker untuk komunikasi dua arah.\n\nBisa tidak saling ditukar?\n\nKamera indoor yang dipasang di luar ruangan akan cepat rusak karena kelembapan dan debu masuk ke sirkuitnya. Kerusakan biasanya tidak langsung terlihat — kamera bekerja normal selama beberapa minggu, lalu mulai muncul kabut di lensa, gambar berbintik, dan akhirnya mati total. Garansi biasanya tidak menanggung kerusakan ini karena pemasangan di luar spesifikasi.\n\nSebaliknya, kamera outdoor di dalam ruangan tidak rusak, tapi biasanya lebih mahal dan bentuknya kurang pas untuk interior.\n\nAda juga kamera yang dipasang di area setengah terbuka — seperti carport beratap atau teras. Untuk lokasi seperti ini, kamera outdoor tetap pilihan yang lebih aman meski terlindung atap, karena kelembapan tetap tinggi dan percikan hujan miring tetap bisa mengenai bodi kamera.\n\nSoal pemasangan kabel\n\nKamera outdoor lazimnya memakai kabel (analog HD atau IP), sedangkan kamera indoor banyak yang WiFi. Bila memilih kamera outdoor WiFi, pastikan titik pemasangan mendapat sinyal kuat — dinding tebal dan jarak jauh dari router adalah penyebab paling umum kamera WiFi outdoor sering terputus.\n\nSoal daya listrik\n\nSering terlewat: kamera indoor WiFi tetap butuh colokan di dekatnya. Titik plafon yang jauh dari sumber listrik berarti tetap ada penarikan kabel daya — hanya lebih pendek dari kabel video. Pertimbangkan ini saat menentukan titik pemasangan kamera indoor, terutama di plafon tinggi.\n\nTips singkat sebelum membeli\n\nCatat dulu lokasi pemasangan yang Anda rencanakan, apakah tertutup atau terbuka. Bila ragu, pilih kamera outdoor — harganya sedikit lebih tinggi tapi jauh lebih tahan lama. Konsultasikan daftar titik pemasangan Anda sebelum membeli supaya tidak salah spesifikasi, dan pastikan jumlah kamera sesuai kebutuhan nyata, bukan tebakan.",
                'reading_time' => 6,
                'package_slug' => 'paket-cctv-rumah-2-kamera',
            ],
            [
                'title' => 'Berapa Lama Rekaman CCTV Tersimpan? Memahami Kapasitas dan Retensi',
                'category' => 'teknologi-cctv',
                'tags' => ['cctv'],
                'excerpt' => 'Rekaman CCTV tidak tersimpan selamanya. Memahami hubungan jumlah kamera, resolusi, dan ukuran harddisk membantu menentukan berapa lama rekaman bisa diakses.',
                'body' => "Salah satu pertanyaan yang paling sering muncul saat instalasi CCTV: berapa lama rekaman tersimpan sebelum tertimpa rekaman baru? Jawabannya selalu sama: tergantung. Tapi tergantung pada hal-hal yang bisa Anda hitung sejak awal.\n\nFaktor yang menentukan durasi\n\nTiga hal menentukan panjang penyimpanan: jumlah kamera, resolusi kamera, dan ukuran harddisk. Semakin banyak kamera dan semakin tinggi resolusinya, semakin cepat ruang penyimpanan terisi.\n\nSebagai perkiraan kasar untuk sistem modern dengan kompresi H.265:\n\n- 1 kamera 2MP, rekaman terus-menerus: sekitar 15–20 GB per hari\n- 4 kamera 2MP, rekaman terus-menerus: sekitar 60–80 GB per hari\n- 4 kamera 2MP, rekaman hanya saat ada gerakan: sekitar 15–30 GB per hari\n\nDengan harddisk 2 TB, empat kamera mode gerakan bisa menyimpan rekaman sekitar dua sampai tiga bulan. Mode terus-menerus memangkasnya menjadi sekitar tiga sampai empat minggu.\n\nMode rekaman berbasis gerakan\n\nSebagian besar sistem modern bisa diatur untuk merekam hanya saat sensor mendeteksi gerakan. Mode ini jauh lebih hemat ruang dan memudahkan pencarian — Anda tidak perlu memutar rekaman kosong berjam-jam untuk menemukan kejadian sepuluh detik.\n\nKekurangannya: gerakan yang tidak terdeteksi (misalnya objek sangat pelan atau jauh) tidak terekam. Untuk area kritis seperti kasir, mode terus-menerus tetap lebih aman. Banyak sistem menawarkan mode campuran: terus-menerus untuk kamera tertentu, berbasis gerakan untuk sisanya.\n\nHarddisk khusus CCTV itu penting\n\nHarddisk biasa tidak dirancang untuk menulis data 24 jam nonstop. Harddisk khusus CCTV seperti WD Purple dibuat untuk beban kerja ini dan umurnya jauh lebih panjang di sistem pengawasan.\n\nMenggunakan harddisk biasa memang bisa jalan di awal, tapi biasanya bermasalah dalam hitungan bulan — sering berupa rekaman yang macet atau perekam yang sering restart. Biaya selisihnya tidak besar dibanding risiko kehilangan rekaman saat dibutuhkan.\n\nBagaimana saat penyimpanan penuh\n\nSistem CCTV modern bekerja secara melingkar: saat harddisk penuh, rekaman tertua otomatis tertimpa oleh rekaman baru. Ini perilaku normal dan bukan kerusakan. Yang penting dipahami: begitu sebuah kejadian lewat dari periode penyimpanan, rekamannya tidak bisa dikembalikan.\n\nKalau ada kejadian penting, unduh atau ekspor rekamannya segera — jangan menunggu. Semua perekam modern punya fitur ekspor ke USB atau unduhan lewat aplikasi.\n\nSoal backup rekaman\n\nUntuk kejadian yang benar-benar penting, beberapa pemilik rumah menyalin rekaman ke penyimpanan terpisah secara berkala. Praktik ini tidak wajib, tapi berguna bila Anda khawatir perekam ikut hilang saat pencurian — skenario di mana rekaman yang paling dibutuhkan justru ikut pergi.\n\nTips praktis\n\nTentukan dulu berapa lama Anda ingin bisa meninjau rekaman. Untuk kebutuhan umum, dua sampai empat minggu biasanya cukup. Bila butuh lebih lama, pertimbangkan menambah kapasitas harddisk atau mengurangi jumlah kamera yang merekam terus-menerus. Sampaikan kebutuhan retensi ini saat konsultasi supaya kapasitas penyimpanan dihitung sejak awal, bukan ditambah belakangan dengan biaya kunjungan ulang.",
                'reading_time' => 6,
                'package_slug' => null,
            ],
            [
                'title' => 'Posisi Pemasangan Kamera CCTV yang Efektif untuk Rumah',
                'category' => 'instalasi-perawatan',
                'tags' => ['cctv', 'instalasi', 'keamanan-rumah'],
                'excerpt' => 'Kamera bagus dengan posisi salah tetap menghasilkan rekaman yang tidak berguna. Empat prinsip dasar penempatan kamera CCTV di rumah.',
                'body' => "Kualitas gambar kamera tidak berarti banyak bila posisinya salah. Empat prinsip berikut menentukan apakah rekaman CCTV Anda benar-benar berguna saat dibutuhkan.\n\n1. Pasang di ketinggian 2,5–3 meter\n\nTerlalu rendah, kamera mudah dijangkau dan dirusak. Terlalu tinggi, yang terekam hanya atap kepala orang lewat. Ketinggian sekitar 2,5 sampai 3 meter memberi keseimbangan: cukup tinggi untuk aman, cukup rendah untuk menangkap wajah.\n\nAturan praktisnya: bila Anda harus mendongak jauh untuk melihat kamera, kemungkinan besar kamera itu terlalu tinggi untuk identifikasi wajah.\n\n2. Arahkan ke pintu dan jalur masuk, bukan ke halaman kosong\n\nArea terbuka yang luas memang mudah dipantau, tapi kebanyakan insiden terjadi di titik masuk: pintu depan, pintu belakang, jendela samping, dan gerbang. Satu kamera yang mengawasi pintu depan lebih berguna daripada dua kamera yang mengawasi halaman kosong.\n\nUntuk rumah, urutan prioritas penempatan yang umum: pintu depan, carport atau area parkir, area belakang, lalu ruang keluarga atau ruang utama.\n\n3. Hindari langsung ke arah sumber cahaya\n\nKamera yang menghadap langsung ke lampu atau matahari pagi akan menghasilkan siluet hitam — subjeknya tidak terlihat. Perhatikan arah cahaya sepanjang hari, terutama untuk kamera outdoor yang menghadap jalan.\n\nKesalahan yang sering terjadi: kamera dipasang menghadap timur atau barat tanpa mempertimbangkan posisi matahari pagi dan sore. Uji dulu dengan berdiri di titik pemasangan pada pagi dan sore hari sebelum mengebor.\n\n4. Perhatikan sudut mati antar kamera\n\nDua kamera yang mengawasi area yang sama adalah pemborosan. Sebaliknya, celah kecil di antara dua area pantauan bisa jadi titik buta yang justru dimanfaatkan. Sketsa sederhana denah rumah dan arah pandang tiap kamera biasanya cukup untuk menemukan celah ini.\n\nUntuk kamera indoor\n\nDi dalam rumah, sudut di sudut ruangan (ceiling corner) biasanya paling efektif karena mencakup hampir seluruh ruangan. Hindari memasang kamera di area privat seperti kamar mandi dan kamar tidur anggota keluarga — selain soal kenyamanan, ini juga soal etika.\n\nKamera indoor yang menghadap pintu masuk ruangan sering lebih berguna daripada yang menghadap isi ruangan, karena menangkap siapa yang masuk dan keluar.\n\nSoal ketinggian dan akses perawatan\n\nKamera yang terlalu tinggi sulit dibersihkan lensanya. Pertimbangkan: kamera yang bisa dijangkau dengan tangga lipat akan dirawat lebih rutin daripada yang butuh tangga besar.\n\nSoal jalur kabel\n\nPosisi kamera menentukan panjang kabel yang dibutuhkan — dan panjang kabel menentukan biaya. Titik yang terlihat ideal di denah bisa jadi mahal karena harus melintasi plafon ganda atau dinding yang tidak bisa ditembus. Teknisi yang baik menyebutkan hal ini saat survei, bukan setelah pekerjaan dimulai.\n\nKapan perlu survei profesional\n\nUntuk rumah standar, empat prinsip di atas biasanya cukup. Untuk bangunan dengan banyak sudut, halaman luas, atau area dengan risiko khusus, survei lokasi profesional membantu memastikan tidak ada titik buta yang terlewat — dan sekaligus menghitung panjang kabel yang realistis sebelum pekerjaan dimulai.",
                'reading_time' => 6,
                'package_slug' => 'paket-cctv-rumah-4-kamera',
            ],
            [
                'title' => 'CCTV WiFi atau Kabel: Mana yang Cocok untuk Anda',
                'category' => 'panduan-memilih-cctv',
                'tags' => ['cctv', 'instalasi'],
                'excerpt' => 'Kamera WiFi mudah dipasang tapi bergantung pada jaringan. Kamera kabel lebih stabil tapi butuh penarikan kabel. Perbandingan singkat untuk keputusan yang tepat.',
                'body' => "Dua pendekatan utama dalam sistem CCTV modern: kamera yang terhubung WiFi dan kamera yang terhubung kabel. Keduanya punya tempat masing-masing — dan memilih yang salah biasanya berarti membayar dua kali.\n\nKamera WiFi: cepat dan fleksibel\n\nKeunggulan utama kamera WiFi adalah kemudahan pemasangan. Tidak perlu menarik kabel video — cukup sumber daya listrik di dekat titik pemasangan. Untuk rumah sewa atau bangunan yang tidak boleh dibongkar, ini pilihan yang masuk akal.\n\nKekurangannya ada dua. Pertama, kualitas gambar bergantung pada kekuatan sinyal WiFi di titik pemasangan — dinding tebal bisa membuat kamera sering terputus. Kedua, sebagian besar kamera WiFi konsumen menyimpan rekaman di kartu microSD dengan kapasitas terbatas, bukan di harddisk besar.\n\nAda juga soal keamanan: kamera WiFi yang memakai password lemah atau firmware lama bisa jadi pintu masuk ke jaringan rumah. Pilih merek yang rutin mengeluarkan pembaruan firmware dan selalu ganti password bawaan.\n\nKamera kabel (IP atau analog HD)\n\nKamera kabel menawarkan koneksi yang stabil karena tidak terpengaruh sinyal WiFi. Sistem IP modern bahkan menyediakan daya lewat kabel yang sama (PoE), jadi penarikan kabel tetap sederhana — satu kabel UTP per kamera, tanpa adaptor terpisah di tiap titik.\n\nSistem kabel terhubung ke perekam (NVR/DVR) dengan harddisk berkapasitas besar, sehingga rekaman bisa tersimpan berbulan-bulan. Untuk instalasi empat kamera atau lebih, sistem kabel hampir selalu pilihan yang lebih andal.\n\nKekurangannya jelas: butuh penarikan kabel, yang berarti pemasangan lebih lama dan biaya awal lebih tinggi. Kabel yang dijalankan juga harus rapi — kabel yang menggantung sembarangan cepat rusak dan terlihat buruk.\n\nSoal biaya jangka panjang\n\nKamera WiFi terlihat lebih murah di awal, tapi biaya bisa naik saat kartu microSD harus ditambah atau diganti, atau saat kamera harus dipindah karena sinyal ternyata lemah. Sistem kabel lebih mahal di awal, tapi biaya perawatannya rendah dan umur pakainya lebih panjang.\n\nPendekatan campuran\n\nBanyak rumah memakai kombinasi: sistem kabel untuk titik-titik kritis outdoor (depan, carport, belakang) dan satu-dua kamera WiFi indoor untuk kebutuhan ringan seperti memantau bayi atau hewan peliharaan.\n\nPendekatan ini masuk akal karena kebutuhannya memang berbeda: titik outdoor butuh rekaman andal jangka panjang, sementara kamera indoor sifatnya opsional dan sementara.\n\nRekomendasi singkat\n\n- Rumah sewa, butuh cepat, satu-dua titik: kamera WiFi\n- Rumah milik sendiri, empat titik atau lebih: sistem kabel (IP dengan PoE)\n- Toko, kantor, gudang: sistem kabel, tanpa pengecualian\n- Area yang jauh dari router dan sulit ditarik kabel: pertimbangkan kamera WiFi outdoor dengan sinyal teruji\n\nBila ragu, hitung dulu berapa titik yang ingin diawasi dan seberapa penting rekaman jangka panjang untuk Anda. Keputusan ini lebih menentukan daripada merek kamera. Konsultasi singkat sebelum membeli biasanya cukup untuk menentukan arah — dan menghindari beli dua kali.",
                'reading_time' => 6,
                'package_slug' => 'paket-cctv-rumah-4-kamera',
            ],
            [
                'title' => 'Perawatan CCTV Berkala: Lima Hal yang Perlu Diperiksa',
                'category' => 'instalasi-perawatan',
                'tags' => ['cctv', 'perawatan'],
                'excerpt' => 'Sistem CCTV bukan pasang sekali lalu lupa. Lima pemeriksaan sederhana yang menjaga sistem tetap berfungsi saat paling dibutuhkan.',
                'body' => "Kegagalan CCTV paling mahal adalah yang baru disadari saat rekaman dibutuhkan — dan ternyata kamera sudah berminggu-minggu mati. Pemeriksaan berkala mencegah hal ini, dan sebagian besar bisa dilakukan sendiri dalam beberapa menit.\n\n1. Periksa apakah semua kamera masih merekam\n\nBuka tampilan perekam dan pastikan setiap kanal menampilkan gambar. Kamera yang mati atau menampilkan layar hitam butuh ditindaklanjuti segera. Lakukan ini seminggu sekali — cukup satu menit.\n\nJangan hanya mengandalkan aplikasi ponsel: beberapa masalah (misalnya kamera yang kehilangan koneksi ke perekam) tidak selalu terlihat sama dari aplikasi dan dari layar perekam langsung.\n\n2. Bersihkan lensa dan housing\n\nKamera outdoor mengumpulkan debu, sarang laba-laba, dan noda hujan dalam hitungan bulan. Lensa kotor menghasilkan gambar buram yang sering disalahartikan sebagai kamera rusak. Bersihkan dengan kain microfiber kering setiap dua sampai tiga bulan.\n\nUntuk noda membandel, kain yang sedikit dibasahi air bersih cukup. Hindari cairan pembersih yang keras — pelapis lensa kamera sensitif terhadap bahan kimia.\n\n3. Periksa tanggal dan jam perekam\n\nJam perekam yang meleset membuat rekaman sulit dipakai sebagai bukti — timestamp tidak cocok dengan kejadian. Periksa dan koreksi setiap beberapa bulan, atau pastikan perekam tersinkronisasi internet.\n\nSelisih beberapa menit mungkin terlihat sepele, tapi bila rekaman akan digunakan sebagai rujukan kejadian (misalnya untuk laporan), selisih itu bisa menjadi soal. Perekam yang tersambung internet umumnya tersinkronisasi otomatis — pastikan fitur itu aktif.\n\n4. Cek sisa kapasitas harddisk\n\nBuka pengaturan perekam dan lihat sisa ruang penyimpanan. Harddisk yang penuh biasanya menimpa rekaman lama otomatis — perilaku normal, tapi penting diketahui agar Anda tahu berapa lama rekaman tersimpan.\n\nPerhatikan juga kesehatan harddisk bila perekam menyediakan fiturnya (S.M.A.R.T). Penurunan kesehatan harddisk adalah tanda awal kegagalan rekaman yang paling sering terlewat.\n\n5. Uji akses jarak jauh dari ponsel\n\nBuka aplikasi dan pastikan semua kamera bisa dilihat dari luar rumah. Masalah koneksi sering muncul setelah ganti router atau perubahan pengaturan jaringan, dan baru disadari saat sedang tidak di rumah.\n\nUji juga notifikasi gerakan bila fiturnya aktif — kadang notifikasi berhenti bekerja tanpa tanda peringatan setelah pembaruan aplikasi.\n\n6. Periksa kondisi fisik kabel dan konektor\n\nKabel yang menggantung atau tertarik akan lambat laun bermasalah di titik konektornya. Periksa secara visual apakah ada kabel yang tertarik, tertekuk tajam, atau konektor yang longgar dari portnya — terutama setelah renovasi atau pemindahan perabot besar.\n\nKonektor kamera outdoor yang longgar adalah sumber masalah paling umum setelah musim hujan: air masuk sedikit demi sedikit hingga kamera mati tanpa gejala awal yang jelas.\n\nKapan panggil teknisi\n\nPemeriksaan di atas bisa dilakukan sendiri. Panggil teknisi bila: kamera menampilkan garis atau bintik, perekam sering restart sendiri, rekaman macet di jam tertentu, atau kamera outdoor menunjukkan tanda masuk air (kabut di dalam lensa).\n\nPerawatan berkala oleh teknisi — biasanya setiap enam sampai dua belas bulan — juga memeriksa hal yang tidak terlihat: kestabilan daya, kondisi konektor, keketatan pemasangan fisik, dan pembaruan firmware perekam.",
                'reading_time' => 6,
                'package_slug' => null,
            ],
            [
                'title' => 'Manfaat CCTV untuk Toko: Lebih dari Sekadar Merekam Pencuri',
                'category' => 'tips-keamanan',
                'tags' => ['cctv', 'keamanan-rumah'],
                'excerpt' => 'Sistem CCTV di toko bukan hanya untuk menangkap pencuri. Empat manfaat operasional yang sering terlewat pemilik usaha.',
                'body' => "Banyak pemilik toko memasang CCTV dengan satu alasan: mencegah kehilangan barang. Alasan itu sah, tapi sistem yang dipasang dengan benar memberi manfaat yang jauh lebih luas.\n\n1. Bukti saat terjadi sengketa\n\nPelanggan mengaku sudah membayar, karyawan mengaku uang cocok, kurir mengaku paket sudah diserahkan — sengketa kecil seperti ini selesai dalam hitungan menit bila ada rekaman. Tanpa rekaman, sengketa berujung pada klaim saling lempar.\n\nUntuk area kasir, rekaman yang menampilkan wajah pelanggan dan meja transaksi sekaligus jauh lebih berguna daripada kamera yang hanya mengarah ke belakang meja.\n\n2. Pemantauan saat tidak di tempat\n\nPemilik toko tidak mungkin berada di lokasi setiap jam buka. Akses jarak jauh dari ponsel memungkinkan pemeriksaan singkat kapan saja: apakah kasir berjalan normal, apakah stok di rak teratur, apakah area gudang aman.\n\nFitur notifikasi gerakan bisa diatur hanya untuk jam tertentu — misalnya di luar jam operasional — sehingga ponsel tidak banjir pemberitahuan saat toko ramai.\n\n3. Data operasional yang tidak terduga\n\nRekaman CCTV sering menjawab pertanyaan operasional yang tidak terduga: jam ramai pelanggan, area rak yang paling sering dikunjungi, atau bagaimana antrean terbentuk di jam sibuk. Informasi ini membantu pengaturan jadwal staf dan tata letak.\n\nBeberapa pemilik toko juga menemukan kebiasaan operasional yang tidak terdokumentasi — misalnya staf yang membuka pintu belakang untuk keperluan pribadi, atau area gudang yang jarang diperiksa.\n\n4. Efek pencegahan\n\nKamera yang terlihat jelas di pintu masuk mengurangi insiden sebelum terjadi. Ini bukan jaminan, tapi efek pencegahan dari kehadiran kamera sudah lama dikenal di industri ritel.\n\nEfek pencegahan bekerja paling baik saat kamera jelas terlihat — bukan tersembunyi. Kamera tersembunyi berguna untuk investigasi, tapi tidak mencegah apa pun.\n\nPenempatan yang tepat untuk toko\n\nTiga titik dengan prioritas tertinggi: area kasir (untuk bukti transaksi), pintu masuk (untuk identifikasi wajah), dan area rak bernilai tinggi. Toko dengan gudang belakang perlu satu kamera tambahan di pintu gudang.\n\nUntuk identifikasi wajah di kasir, kamera harus dipasang menghadap langsung ke area wajah pelanggan dan kasir — bukan dari belakang meja yang hanya menangkap punggung kepala. Ketinggian sekitar 2,5 meter dengan kemiringan moderat biasanya memberi hasil terbaik.\n\nSoal penyimpanan rekaman\n\nToko umumnya butuh retensi lebih panjang daripada rumah karena sengketa bisa muncul berminggu-minggu setelah kejadian. Empat kamera dengan harddisk 2 TB mode gerakan biasanya cukup untuk retensi satu bulan lebih. Kalau toko sering mengalami sengketa atau punya area rawan tambahan, naikkan kapasitasnya.\n\nMulai dari yang realistis\n\nToko kecil tidak butuh enam belas kamera. Empat kamera yang dipasang di titik yang tepat jauh lebih berguna daripada delapan kamera yang posisinya asal. Konsultasikan denah toko Anda sebelum memutuskan jumlah titik — survei singkat biasanya menemukan titik yang terlewat dan memangkas titik yang tidak perlu.",
                'reading_time' => 6,
                'package_slug' => 'paket-cctv-toko-4-kamera',
            ],
        ];

        $publishedAt = now()->subDays(2);

        foreach ($articles as $article) {
            $model = BlogArticle::updateOrCreate(
                ['slug' => Str::slug($article['title'])],
                [
                    'user_id' => $author->id,
                    'blog_category_id' => $catId($article['category']),
                    'title' => $article['title'],
                    'excerpt' => $article['excerpt'],
                    'body' => $article['body'],
                    'status' => 'published',
                    'published_at' => $publishedAt,
                    'reading_time' => $article['reading_time'],
                    'published' => true,
                ],
            );

            $model->tags()->sync($tagIds($article['tags']));

            SeoMetadata::updateOrCreate(
                ['seoable_type' => $model->getMorphClass(), 'seoable_id' => $model->id],
                [
                    'seo_title' => $article['title'].' | Blog WP CCTV Indonesia',
                    'meta_description' => $article['excerpt'],
                    'og_type' => 'article',
                ],
            );

            $publishedAt = $publishedAt->copy()->subDays(3);
        }

        // One draft article keeps the admin draft workflow demonstrable
        // without ever appearing on the public site.
        BlogArticle::firstOrCreate(
            ['slug' => 'draft-panduan-kamera-ptz-untuk-area-luas'],
            [
                'user_id' => $author->id,
                'blog_category_id' => $catId('teknologi-cctv'),
                'title' => 'Panduan Kamera PTZ untuk Area Luas',
                'excerpt' => 'Kapan kamera PTZ lebih tepat daripada beberapa kamera tetap.',
                'body' => 'Artikel ini masih dalam penyusunan dan belum dipublikasikan.',
                'status' => 'draft',
                'published' => false,
            ],
        );
    }
}
