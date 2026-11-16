@extends('layouts.public')

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
                <ol class="flex flex-wrap gap-1.5">
                    <li><a class="hover:text-brand-blue" href="{{ route('home') }}">Beranda</a></li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" class="font-semibold text-brand-navy">Produk</li>
                </ol>
            </nav>
            <h1 class="mt-3 text-2xl font-bold text-brand-navy sm:text-3xl">Katalog Produk CCTV</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">Referensi perangkat CCTV untuk membantu memilih kamera, recorder, dan aksesori yang sesuai kebutuhan lokasi Anda. Perangkat tidak dijual online — konsultasikan pemilihannya lewat WhatsApp.</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[260px_1fr]">
            <form method="GET" action="{{ route('products.index') }}" class="h-fit rounded-lg border border-slate-200 bg-white p-5" aria-label="Filter produk">
                <h2 class="text-sm font-bold text-brand-navy">Filter</h2>

                <div class="mt-4">
                    <label for="filter-brand" class="block text-xs font-semibold text-slate-700">Merek</label>
                    <select id="filter-brand" name="brand" class="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700">
                        <option value="">Semua merek</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->slug }}" @selected($activeBrand?->is($brand))>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-4">
                    <label for="filter-type" class="block text-xs font-semibold text-slate-700">Jenis perangkat</label>
                    <select id="filter-type" name="camera_type" class="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700">
                        <option value="">Semua jenis</option>
                        @foreach($cameraTypes as $value => $label)
                            <option value="{{ $value }}" @selected($activeCameraType === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-5 flex gap-2">
                    <button type="submit" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-md bg-brand-blue px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Terapkan</button>
                    <a href="{{ route('products.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
                </div>
            </form>

            <div>
                @if($products->isNotEmpty())
                    <p class="text-sm text-slate-500" role="status">{{ $products->total() }} produk ditemukan</p>
                    <div class="mt-5 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($products as $product)
                            @include('public.partials.product-card', ['product' => $product])
                        @endforeach
                    </div>
                    {{ $products->links('pagination::tailwind') }}
                @else
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 class="text-base font-semibold text-brand-navy">Tidak ada produk yang cocok</h2>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-slate-600">Coba hapus filter, atau tanyakan langsung kebutuhan Anda — kami carikan perangkat yang sesuai.</p>
                        <div class="mt-5 flex flex-wrap justify-center gap-3">
                            <a href="{{ route('products.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset filter</a>
                            @include('public.partials.whatsapp-cta')
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
