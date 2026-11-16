@extends('layouts.public')

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
                <ol class="flex flex-wrap gap-1.5">
                    <li><a class="hover:text-brand-blue" href="{{ route('home') }}">Beranda</a></li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" class="font-semibold text-brand-navy">Paket CCTV</li>
                </ol>
            </nav>
            <h1 class="mt-3 text-2xl font-bold text-brand-navy sm:text-3xl">Paket Instalasi CCTV untuk Rumah dan Bisnis</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">Temukan solusi instalasi CCTV berdasarkan jumlah kamera dan kebutuhan lokasi Anda. Setiap paket adalah penawaran jasa pemasangan — bukan penjualan barang online.</p>
            <div class="mt-5">
                @include('public.partials.whatsapp-cta', ['waLabel' => 'Konsultasi Instalasi'])
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[260px_1fr]">
            <form method="GET" action="{{ route('packages.index') }}" class="h-fit rounded-lg border border-slate-200 bg-white p-5" aria-label="Filter paket">
                <h2 class="text-sm font-bold text-brand-navy">Filter</h2>

                <div class="mt-4">
                    <label for="filter-cameras" class="block text-xs font-semibold text-slate-700">Jumlah kamera</label>
                    <select id="filter-cameras" name="cameras" class="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700">
                        <option value="">Semua</option>
                        @foreach($cameraQuantities as $qty)
                            <option value="{{ $qty }}" @selected($activeCameras === $qty)>{{ $qty }} kamera</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-4">
                    <label for="filter-usage" class="block text-xs font-semibold text-slate-700">Kategori penggunaan</label>
                    <select id="filter-usage" name="usage" class="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700">
                        <option value="">Semua</option>
                        @foreach($usageCategories as $category)
                            <option value="{{ $category->slug }}" @selected($activeUsageSlug === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-4">
                    <label for="filter-category" class="block text-xs font-semibold text-slate-700">Kategori paket</label>
                    <select id="filter-category" name="category" class="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700">
                        <option value="">Semua</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->slug }}" @selected($activeCategorySlug === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-5 flex gap-2">
                    <button type="submit" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-md bg-brand-blue px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Terapkan</button>
                    <a href="{{ route('packages.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
                </div>
            </form>

            <div>
                @if($packages->isNotEmpty())
                    <p class="text-sm text-slate-500" role="status">{{ $packages->total() }} paket ditemukan</p>
                    <div class="mt-5 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($packages as $package)
                            @include('public.partials.package-card', ['package' => $package])
                        @endforeach
                    </div>
                    {{ $packages->links('pagination::tailwind') }}
                @else
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 class="text-base font-semibold text-brand-navy">Tidak ada paket yang cocok</h2>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-slate-600">Coba hapus filter, atau ceritakan kebutuhan Anda — kami susun rekomendasi paket yang sesuai lokasi Anda.</p>
                        <div class="mt-5 flex flex-wrap justify-center gap-3">
                            <a href="{{ route('packages.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset filter</a>
                            @include('public.partials.whatsapp-cta', ['waLabel' => 'Konsultasi Instalasi'])
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
