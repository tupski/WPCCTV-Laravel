@extends('layouts.public')

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
                <ol class="flex flex-wrap gap-1.5">
                    <li><a class="hover:text-brand-blue" href="{{ route('home') }}">Beranda</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a class="hover:text-brand-blue" href="{{ route('products.index') }}">Produk</a></li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" class="font-semibold text-brand-navy">{{ $product->name }}</li>
                </ol>
            </nav>

            <div class="mt-6 grid gap-8 lg:grid-cols-2">
                <div class="flex aspect-[4/3] items-center justify-center rounded-xl bg-brand-navy" aria-hidden="true">
                    <svg class="h-28 w-28 text-brand-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </div>

                <div>
                    @if($product->brand !== null)
                        <p class="text-sm font-semibold uppercase tracking-wide text-brand-blue">{{ $product->brand->name }}</p>
                    @endif
                    <h1 class="mt-2 text-2xl font-bold text-brand-navy sm:text-3xl">{{ $product->name }}</h1>
                    @if($product->model)
                        <p class="mt-1 text-sm text-slate-500">Model: {{ $product->model }}</p>
                    @endif

                    @if($product->description)
                        <p class="mt-4 text-sm leading-7 text-slate-600">{{ $product->description }}</p>
                    @endif

                    <div class="mt-6">
                        @include('public.partials.whatsapp-cta', [
                            'waUrl' => \App\Support\WhatsAppLink::forProduct($product->slug),
                            'waLabel' => 'Konsultasikan Produk Ini',
                            'waSize' => 'lg',
                        ])
                    </div>
                    <p class="mt-3 text-xs leading-6 text-slate-500">Perangkat adalah referensi — tidak dijual online. Konsultasi mencakup rekomendasi model, jumlah unit, dan estimasi pemasangan.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[1fr_320px]">
            <div class="space-y-10">
                @if(is_iterable($product->key_features) && count($product->key_features) > 0)
                    <section aria-labelledby="fitur-utama">
                        <h2 id="fitur-utama" class="text-xl font-bold text-brand-navy">Fitur Utama</h2>
                        <ul class="mt-4 space-y-2.5">
                            @foreach($product->key_features as $feature)
                                <li class="flex gap-2.5 text-sm leading-7 text-slate-600"><svg class="mt-1.5 h-4 w-4 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>{{ $feature }}</span></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if(is_iterable($product->technical_specs) && count($product->technical_specs) > 0)
                    <section aria-labelledby="spesifikasi-teknis">
                        <h2 id="spesifikasi-teknis" class="text-xl font-bold text-brand-navy">Spesifikasi Teknis</h2>
                        <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                            <table class="w-full min-w-[28rem] text-sm">
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($product->technical_specs as $spec)
                                        <tr class="even:bg-slate-50">
                                            @php $parts = array_map('trim', explode(':', $spec, 2)); @endphp
                                            @if(count($parts) === 2)
                                                <th scope="row" class="w-2/5 px-4 py-3 text-left font-semibold text-brand-navy">{{ $parts[0] }}</th>
                                                <td class="px-4 py-3 text-slate-600">{{ $parts[1] }}</td>
                                            @else
                                                <td colspan="2" class="px-4 py-3 text-slate-600">{{ $spec }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                @if(is_iterable($product->recommended_applications) && count($product->recommended_applications) > 0)
                    <section aria-labelledby="aplikasi-rekomendasi">
                        <h2 id="aplikasi-rekomendasi" class="text-xl font-bold text-brand-navy">Aplikasi yang Direkomendasikan</h2>
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach($product->recommended_applications as $application)
                                <li class="rounded-full bg-brand-light px-3.5 py-1.5 text-sm font-medium text-brand-navy">{{ $application }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if($faqs->isNotEmpty())
                    <section aria-labelledby="faq-produk">
                        <h2 id="faq-produk" class="text-xl font-bold text-brand-navy">Pertanyaan tentang Produk Ini</h2>
                        <div class="mt-4">
                            @include('public.partials.faq-accordion', ['faqs' => $faqs])
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-6">
                @if($relatedPackages->isNotEmpty())
                    <section class="rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="paket-terkait">
                        <h2 id="paket-terkait" class="text-base font-bold text-brand-navy">Paket Instalasi Terkait</h2>
                        <ul class="mt-3 space-y-3">
                            @foreach($relatedPackages as $package)
                                <li>
                                    <a class="text-sm font-semibold text-brand-blue hover:underline" href="{{ route('packages.show', $package->slug) }}">{{ $package->title }}</a>
                                    @if($package->camera_quantity)
                                        <p class="text-xs text-slate-500">{{ $package->camera_quantity }} kamera</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if($relatedProducts->isNotEmpty())
                    <section class="rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="produk-terkait">
                        <h2 id="produk-terkait" class="text-base font-bold text-brand-navy">Produk Lainnya</h2>
                        <ul class="mt-3 space-y-3">
                            @foreach($relatedProducts as $related)
                                <li>
                                    <a class="text-sm font-semibold text-brand-blue hover:underline" href="{{ route('products.show', $related->slug) }}">{{ $related->name }}</a>
                                    @if($related->brand !== null)
                                        <p class="text-xs text-slate-500">{{ $related->brand->name }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </aside>
        </div>

        <section class="mt-12 rounded-lg bg-brand-navy p-8 text-center" aria-labelledby="cta-produk">
            <h2 id="cta-produk" class="text-xl font-bold text-white sm:text-2xl">Konsultasikan Produk Ini untuk Kebutuhan Instalasi Anda</h2>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-200">Ceritakan lokasi dan kebutuhan pengawasan Anda. Kami rekomendasikan model yang tepat beserta estimasi pemasangannya.</p>
            <div class="mt-6 flex justify-center">
                @include('public.partials.whatsapp-cta', [
                    'waUrl' => \App\Support\WhatsAppLink::forProduct($product->slug),
                    'waLabel' => 'Konsultasikan Produk Ini',
                    'waSize' => 'lg',
                ])
            </div>
        </section>
    </section>
@endsection
