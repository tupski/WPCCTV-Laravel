@extends('layouts.public')

@section('content')
    {{-- ── Section 1: Hero ─────────────────────────────────────────────── --}}
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-14 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-24">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-brand-blue">Jasa CCTV Profesional</p>
                <h1 class="mt-3 text-3xl font-bold leading-tight text-brand-navy sm:text-4xl lg:text-5xl">Solusi CCTV untuk Keamanan Rumah dan Bisnis</h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-slate-600">WP CCTV Indonesia menyediakan layanan instalasi dan servis CCTV untuk kebutuhan rumah, toko, kantor, gudang, sekolah, dan pabrik. Survei lokasi dulu, penawaran jelas, pengerjaan rapi.</p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    @include('public.partials.whatsapp-cta', [
                        'waUrl' => \App\Support\WhatsAppLink::forHome(),
                        'waLabel' => 'Konsultasi via WhatsApp',
                        'waSize' => 'lg',
                    ])
                    <a href="{{ route('packages.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-md border border-brand-navy px-6 py-3 text-base font-semibold text-brand-navy transition hover:bg-brand-light">Lihat Paket CCTV</a>
                </div>
                <ul class="mt-8 grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                    <li class="flex gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Survei lokasi sebelum penawaran</li>
                    <li class="flex gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Instalasi rapi, kabel tersembunyi bila memungkinkan</li>
                    <li class="flex gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Garansi pemasangan sesuai kesepakatan</li>
                    <li class="flex gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Dukungan perawatan setelah pemasangan</li>
                </ul>
            </div>

            <div class="relative hidden lg:block" aria-hidden="true">
                <div class="flex aspect-[4/3] items-center justify-center rounded-xl bg-brand-navy">
                    <svg class="h-40 w-40 text-brand-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Section 2: Main services ───────────────────────────────────── --}}
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-20" aria-labelledby="layanan-utama">
        <h2 id="layanan-utama" class="text-2xl font-bold text-brand-navy sm:text-3xl">Layanan Utama</h2>
        <div class="mt-8 grid gap-6 md:grid-cols-2">
            @foreach($services->take(2) as $service)
                <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-brand-navy">
                        <a class="hover:text-brand-blue" href="{{ route('services.show', $service->slug) }}">{{ $service->title }}</a>
                    </h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">{{ $service->description }}</p>
                    <div class="mt-4">
                        @include('public.partials.whatsapp-cta', [
                            'waUrl' => \App\Support\WhatsAppLink::forService($service->slug),
                            'waLabel' => $service->type === 'repair' ? 'Konsultasi Servis' : 'Konsultasi Instalasi',
                        ])
                    </div>
                </article>
            @endforeach
        </div>
        @if($services->count() > 2)
            <p class="mt-6"><a class="text-sm font-semibold text-brand-blue hover:underline" href="{{ route('services.index') }}">Lihat semua layanan &rarr;</a></p>
        @endif
    </section>

    {{-- ── Section 3: Featured packages ────────────────────────────────── --}}
    @if($packages->isNotEmpty())
        <section class="border-y border-slate-200 bg-white" aria-labelledby="paket-unggulan">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-20">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 id="paket-unggulan" class="text-2xl font-bold text-brand-navy sm:text-3xl">Paket Instalasi CCTV</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">Paket pemasangan dengan jumlah kamera dan cakupan pekerjaan yang jelas. Harga dapat menyesuaikan kondisi lokasi.</p>
                    </div>
                    <a class="text-sm font-semibold text-brand-blue hover:underline" href="{{ route('packages.index') }}">Semua paket &rarr;</a>
                </div>
                <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($packages as $package)
                        @include('public.partials.package-card', ['package' => $package])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ── Section 4: Brands ──────────────────────────────────────────── --}}
    @if($brands->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16" aria-labelledby="brand-produk">
            <h2 id="brand-produk" class="text-2xl font-bold text-brand-navy sm:text-3xl">Merek Perangkat yang Kami Pasang</h2>
            <p class="mt-2 text-sm leading-7 text-slate-600">Perangkat adalah referensi — pemilihan merek dan model menyesuaikan kebutuhan serta anggaran lokasi Anda.</p>
            <ul class="mt-6 flex flex-wrap gap-3">
                @foreach($brands as $brand)
                    <li class="rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-brand-navy">{{ $brand->name }}</li>
                @endforeach
            </ul>
            @if($products->isNotEmpty())
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($products->take(3) as $product)
                        @include('public.partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    {{-- ── Section 5: Why choose us ───────────────────────────────────── --}}
    <section class="border-y border-slate-200 bg-white" aria-labelledby="kenapa-kami">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-20">
            <h2 id="kenapa-kami" class="text-2xl font-bold text-brand-navy sm:text-3xl">Kenapa Memilih Kami</h2>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg border border-slate-200 p-6">
                    <h3 class="text-base font-bold text-brand-navy">Survei sebelum menawarkan</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">Setiap lokasi punya kondisi berbeda. Kami survei dulu supaya penawaran sesuai kebutuhan, bukan tebakan.</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-6">
                    <h3 class="text-base font-bold text-brand-navy">Penjelasan yang jujur</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">Kami jelaskan apa yang bisa dan tidak bisa dilakukan kamera, termasuk batasan penyimpanan rekaman.</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-6">
                    <h3 class="text-base font-bold text-brand-navy">Pengerjaan rapi</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">Kabel dijalankan sependek mungkin dan dirapikan, perangkat dipasang di posisi yang benar-benar efektif.</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-6">
                    <h3 class="text-base font-bold text-brand-navy">Garansi pemasangan</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">Garansi pemasangan mengikuti kesepakatan di penawaran — dijelaskan tertulis sebelum kerja dimulai.</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-6">
                    <h3 class="text-base font-bold text-brand-navy">Perawatan berkelanjutan</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">Sistem CCTV butuh pemeriksaan berkala. Layanan maintenance tersedia setelah pemasangan.</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-6">
                    <h3 class="text-base font-bold text-brand-navy">Respons cepat via WhatsApp</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">Pertanyaan dan permintaan survei dilayani lewat WhatsApp pada jam operasional.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Section 6: Installation process ────────────────────────────── --}}
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-20" aria-labelledby="proses-instalasi">
        <h2 id="proses-instalasi" class="text-2xl font-bold text-brand-navy sm:text-3xl">Proses Instalasi</h2>
        <ol class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach(['Konsultasi kebutuhan via WhatsApp atau telepon', 'Analisis kondisi lokasi dan titik buta', 'Rekomendasi jenis kamera dan posisi pemasangan', 'Penawaran tertulis beserta rincian pekerjaan', 'Instalasi oleh teknisi berpengalaman', 'Pemeriksaan sistem dan panduan penggunaan singkat'] as $index => $step)
                <li class="rounded-lg border border-slate-200 bg-white p-6">
                    <p class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-navy text-sm font-bold text-white" aria-hidden="true">{{ $index + 1 }}</p>
                    <p class="mt-3 text-sm leading-7 text-slate-600">{{ $step }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- ── Section 7: Blog highlights ─────────────────────────────────── --}}
    @if($articles->isNotEmpty())
        <section class="border-y border-slate-200 bg-white" aria-labelledby="artikel-terbaru">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-20">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <h2 id="artikel-terbaru" class="text-2xl font-bold text-brand-navy sm:text-3xl">Artikel Terbaru</h2>
                    <a class="text-sm font-semibold text-brand-blue hover:underline" href="{{ route('blog.index') }}">Semua artikel &rarr;</a>
                </div>
                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    @foreach($articles as $article)
                        @include('public.partials.article-card', ['article' => $article])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ── Section 8: FAQ ─────────────────────────────────────────────── --}}
    @if($faqs->isNotEmpty())
        <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8 lg:py-20" aria-labelledby="faq-beranda">
            <h2 id="faq-beranda" class="text-2xl font-bold text-brand-navy sm:text-3xl">Pertanyaan Umum</h2>
            <div class="mt-8">
                @include('public.partials.faq-accordion', ['faqs' => $faqs])
            </div>
            <p class="mt-6 text-sm text-slate-600"><a class="font-semibold text-brand-blue hover:underline" href="{{ route('faqs.index') }}">Lihat semua pertanyaan &rarr;</a></p>
        </section>
    @endif

    {{-- ── Section 9: Final CTA ───────────────────────────────────────── --}}
    <section class="bg-brand-navy" aria-labelledby="cta-akhir">
        <div class="mx-auto max-w-4xl px-4 py-14 text-center sm:px-6 lg:px-8 lg:py-20">
            <h2 id="cta-akhir" class="text-2xl font-bold text-white sm:text-3xl">Butuh Solusi CCTV untuk Rumah atau Bisnis Anda?</h2>
            <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-slate-200">Ceritakan kebutuhan Anda — jumlah titik kamera, lokasi, dan kondisi yang ingin diawasi. Kami bantu pilih solusi yang tepat, tanpa paksaan.</p>
            <div class="mt-8 flex justify-center">
                @include('public.partials.whatsapp-cta', [
                    'waUrl' => \App\Support\WhatsAppLink::forHome(),
                    'waLabel' => 'Konsultasi Sekarang',
                    'waSize' => 'lg',
                ])
            </div>
        </div>
    </section>
@endsection
