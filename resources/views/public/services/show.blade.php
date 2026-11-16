@extends('layouts.public')

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
                <ol class="flex flex-wrap gap-1.5">
                    <li><a class="hover:text-brand-blue" href="{{ route('home') }}">Beranda</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a class="hover:text-brand-blue" href="{{ route('services.index') }}">Layanan</a></li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" class="font-semibold text-brand-navy">{{ $service->title }}</li>
                </ol>
            </nav>

            <div class="mt-6 max-w-3xl">
                <h1 class="text-2xl font-bold text-brand-navy sm:text-3xl">{{ $service->title }}</h1>
                @if($service->description)
                    <p class="mt-4 text-sm leading-7 text-slate-600">{{ $service->description }}</p>
                @endif
                <div class="mt-6">
                    @include('public.partials.whatsapp-cta', [
                        'waUrl' => \App\Support\WhatsAppLink::forService($service->slug),
                        'waLabel' => $service->type === 'repair' ? 'Konsultasi Servis' : 'Konsultasi Instalasi',
                        'waSize' => 'lg',
                    ])
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[1fr_320px]">
            <div class="space-y-10">
                @if(is_iterable($service->scope) && count($service->scope) > 0)
                    <section aria-labelledby="cakupan-layanan">
                        <h2 id="cakupan-layanan" class="text-xl font-bold text-brand-navy">Cakupan Layanan</h2>
                        <ul class="mt-4 space-y-2.5">
                            @foreach($service->scope as $item)
                                <li class="flex gap-2.5 text-sm leading-7 text-slate-600"><svg class="mt-1.5 h-4 w-4 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if(is_iterable($service->applications) && count($service->applications) > 0)
                    <section aria-labelledby="aplikasi-layanan">
                        <h2 id="aplikasi-layanan" class="text-xl font-bold text-brand-navy">Untuk Lokasi Seperti Apa</h2>
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach($service->applications as $application)
                                <li class="rounded-full bg-brand-light px-3.5 py-1.5 text-sm font-medium text-brand-navy">{{ $application }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if(is_iterable($service->process) && count($service->process) > 0)
                    <section aria-labelledby="proses-layanan">
                        <h2 id="proses-layanan" class="text-xl font-bold text-brand-navy">Alur Pengerjaan</h2>
                        <ol class="mt-4 space-y-3">
                            @foreach($service->process as $index => $step)
                                <li class="flex gap-3 text-sm leading-7 text-slate-600">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-light text-xs font-bold text-brand-navy" aria-hidden="true">{{ $index + 1 }}</span>
                                    <span>{{ $step }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                @if($service->type === 'repair')
                    <section class="rounded-lg border border-amber-200 bg-amber-50 p-5" aria-labelledby="batasan-layanan">
                        <h2 id="batasan-layanan" class="text-base font-bold text-amber-900">Batasan Layanan Perbaikan</h2>
                        <p class="mt-2 text-sm leading-7 text-amber-800">Perbaikan dilakukan setelah pemeriksaan. Tidak semua kerusakan bisa diperbaiki di tempat — bila perangkat harus dibawa atau diganti, kami sampaikan dulu estimasi biayanya sebelum melanjutkan.</p>
                    </section>
                @endif

                @if($faqs->isNotEmpty())
                    <section aria-labelledby="faq-layanan">
                        <h2 id="faq-layanan" class="text-xl font-bold text-brand-navy">Pertanyaan tentang Layanan Ini</h2>
                        <div class="mt-4">
                            @include('public.partials.faq-accordion', ['faqs' => $faqs])
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-6">
                @if($relatedPackages->isNotEmpty())
                    <section class="rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="paket-rekomendasi">
                        <h2 id="paket-rekomendasi" class="text-base font-bold text-brand-navy">Rekomendasi Paket</h2>
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

                @if($relatedServices->isNotEmpty())
                    <section class="rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="layanan-lain">
                        <h2 id="layanan-lain" class="text-base font-bold text-brand-navy">Layanan Lainnya</h2>
                        <ul class="mt-3 space-y-3">
                            @foreach($relatedServices as $related)
                                <li><a class="text-sm font-semibold text-brand-blue hover:underline" href="{{ route('services.show', $related->slug) }}">{{ $related->title }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </aside>
        </div>

        <section class="mt-12 rounded-lg bg-brand-navy p-8 text-center" aria-labelledby="cta-layanan">
            <h2 id="cta-layanan" class="text-xl font-bold text-white sm:text-2xl">{{ $service->type === 'repair' ? 'Sistem CCTV Anda Bermasalah?' : 'Siap Memasang CCTV?' }}</h2>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-200">{{ $service->type === 'repair' ? 'Ceritakan gejalanya — kamera mati, rekaman hilang, atau tidak bisa dilihat dari ponsel. Kami bantu diagnosis awal lewat WhatsApp.' : 'Ceritakan lokasi dan kebutuhan pengawasan Anda. Kami bantu dari survei sampai sistem berjalan.' }}</p>
            <div class="mt-6 flex justify-center">
                @include('public.partials.whatsapp-cta', [
                    'waUrl' => \App\Support\WhatsAppLink::forService($service->slug),
                    'waLabel' => $service->type === 'repair' ? 'Konsultasi Servis' : 'Konsultasi Instalasi',
                    'waSize' => 'lg',
                ])
            </div>
        </section>
    </section>
@endsection
