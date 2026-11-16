@extends('layouts.public')

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
                <ol class="flex flex-wrap gap-1.5">
                    <li><a class="hover:text-brand-blue" href="{{ route('home') }}">Beranda</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a class="hover:text-brand-blue" href="{{ route('packages.index') }}">Paket CCTV</a></li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" class="font-semibold text-brand-navy">{{ $package->title }}</li>
                </ol>
            </nav>

            <div class="mt-6 max-w-3xl">
                @if($package->brand !== null)
                    <p class="text-sm font-semibold uppercase tracking-wide text-brand-blue">Perangkat utama: {{ $package->brand->name }}</p>
                @endif
                <h1 class="mt-2 text-2xl font-bold text-brand-navy sm:text-3xl">{{ $package->title }}</h1>
                @if($package->description)
                    <p class="mt-4 text-sm leading-7 text-slate-600">{{ $package->description }}</p>
                @endif
                <div class="mt-6 flex flex-wrap items-center gap-4">
                    @include('public.partials.whatsapp-cta', [
                        'waUrl' => \App\Support\WhatsAppLink::forPackage($package->slug),
                        'waLabel' => 'Konsultasikan Paket Ini',
                        'waSize' => 'lg',
                    ])
                    @if($package->price !== null)
                        <p class="text-lg font-bold text-brand-navy">Mulai Rp{{ number_format((float) $package->price, 0, ',', '.') }}</p>
                    @endif
                </div>
                @if($package->price !== null)
                    <p class="mt-2 text-xs leading-6 text-slate-500">Harga awal dan dapat menyesuaikan kondisi lokasi setelah survei. Tidak ada transaksi online — penawaran final dikirim via WhatsApp.</p>
                @endif
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[1fr_320px]">
            <div class="space-y-10">
                @if($package->camera_quantity)
                    <section aria-labelledby="ringkasan-paket">
                        <h2 id="ringkasan-paket" class="text-xl font-bold text-brand-navy">Ringkasan Paket</h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div class="rounded-lg border border-slate-200 bg-white p-4 text-center">
                                <p class="text-2xl font-bold text-brand-blue">{{ $package->camera_quantity }}</p>
                                <p class="mt-1 text-xs text-slate-500">Titik kamera</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-4 text-center">
                                <p class="text-2xl font-bold text-brand-blue">{{ is_iterable($package->included_equipment) ? count($package->included_equipment) : 0 }}</p>
                                <p class="mt-1 text-xs text-slate-500">Perangkat disertakan</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-4 text-center">
                                <p class="text-2xl font-bold text-brand-blue">{{ is_iterable($package->installation_scope) ? count($package->installation_scope) : 0 }}</p>
                                <p class="mt-1 text-xs text-slate-500">Cakupan pekerjaan</p>
                            </div>
                        </div>
                        @if($package->usageCategories->isNotEmpty())
                            <p class="mt-4 text-sm text-slate-600">Cocok untuk: <span class="font-semibold text-brand-navy">{{ $package->usageCategories->pluck('name')->implode(', ') }}</span></p>
                        @endif
                    </section>
                @endif

                @if(is_iterable($package->included_equipment) && count($package->included_equipment) > 0)
                    <section aria-labelledby="perangkat-disertakan">
                        <h2 id="perangkat-disertakan" class="text-xl font-bold text-brand-navy">Perangkat yang Disertakan</h2>
                        <ul class="mt-4 space-y-2.5">
                            @foreach($package->included_equipment as $item)
                                <li class="flex gap-2.5 text-sm leading-7 text-slate-600"><svg class="mt-1.5 h-4 w-4 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                        <p class="mt-3 text-xs leading-6 text-slate-500">Merek dan model dapat menyesuaikan ketersediaan stok saat pemasangan, dengan kualitas setara atau lebih baik.</p>
                    </section>
                @endif

                @if(is_iterable($package->installation_scope) && count($package->installation_scope) > 0)
                    <section aria-labelledby="cakupan-instalasi">
                        <h2 id="cakupan-instalasi" class="text-xl font-bold text-brand-navy">Cakupan Pekerjaan</h2>
                        <ul class="mt-4 space-y-2.5">
                            @foreach($package->installation_scope as $scope)
                                <li class="flex gap-2.5 text-sm leading-7 text-slate-600"><svg class="mt-1.5 h-4 w-4 shrink-0 text-brand-blue" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span>{{ $scope }}</span></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if(is_iterable($package->exclusions) && count($package->exclusions) > 0)
                    <section aria-labelledby="di-luar-cakupan">
                        <h2 id="di-luar-cakupan" class="text-xl font-bold text-brand-navy">Di Luar Cakupan</h2>
                        <ul class="mt-4 space-y-2.5">
                            @foreach($package->exclusions as $exclusion)
                                <li class="flex gap-2.5 text-sm leading-7 text-slate-600"><svg class="mt-1.5 h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg><span>{{ $exclusion }}</span></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if(is_iterable($package->installation_process) && count($package->installation_process) > 0)
                    <section aria-labelledby="proses-pengerjaan">
                        <h2 id="proses-pengerjaan" class="text-xl font-bold text-brand-navy">Proses Pengerjaan</h2>
                        <ol class="mt-4 space-y-3">
                            @foreach($package->installation_process as $index => $step)
                                <li class="flex gap-3 text-sm leading-7 text-slate-600">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-light text-xs font-bold text-brand-navy" aria-hidden="true">{{ $index + 1 }}</span>
                                    <span>{{ $step }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                @if($faqs->isNotEmpty())
                    <section aria-labelledby="faq-paket">
                        <h2 id="faq-paket" class="text-xl font-bold text-brand-navy">Pertanyaan tentang Paket Ini</h2>
                        <div class="mt-4">
                            @include('public.partials.faq-accordion', ['faqs' => $faqs])
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-6">
                @if($package->categories->isNotEmpty())
                    <section class="rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="kategori-paket">
                        <h2 id="kategori-paket" class="text-base font-bold text-brand-navy">Kategori</h2>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach($package->categories as $category)
                                <li><a class="inline-block rounded-full bg-brand-light px-3 py-1 text-xs font-semibold text-brand-navy hover:bg-blue-100" href="{{ route('packages.index', ['category' => $category->slug]) }}">{{ $category->name }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if($relatedPackages->isNotEmpty())
                    <section class="rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="paket-alternatif">
                        <h2 id="paket-alternatif" class="text-base font-bold text-brand-navy">Paket Alternatif</h2>
                        <ul class="mt-3 space-y-3">
                            @foreach($relatedPackages as $related)
                                <li>
                                    <a class="text-sm font-semibold text-brand-blue hover:underline" href="{{ route('packages.show', $related->slug) }}">{{ $related->title }}</a>
                                    @if($related->camera_quantity)
                                        <p class="text-xs text-slate-500">{{ $related->camera_quantity }} kamera</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </aside>
        </div>

        <section class="mt-12 rounded-lg bg-brand-navy p-8 text-center" aria-labelledby="cta-paket">
            <h2 id="cta-paket" class="text-xl font-bold text-white sm:text-2xl">Ingin Menggunakan Paket Ini?</h2>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-200">Hubungi kami untuk jadwal survei. Penawaran final menyesuaikan kondisi lokasi — tanpa biaya kejutan.</p>
            <div class="mt-6 flex justify-center">
                @include('public.partials.whatsapp-cta', [
                    'waUrl' => \App\Support\WhatsAppLink::forPackage($package->slug),
                    'waLabel' => 'Konsultasikan Paket Ini',
                    'waSize' => 'lg',
                ])
            </div>
        </section>
    </section>
@endsection
