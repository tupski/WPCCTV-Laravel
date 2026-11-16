@extends('layouts.public')

@php
    $waHome = \App\Support\WhatsAppLink::forHome();
    $site = \App\Support\SiteInfo::all();
@endphp

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
                <ol class="flex flex-wrap gap-1.5">
                    <li><a class="hover:text-brand-blue" href="{{ route('home') }}">Beranda</a></li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" class="font-semibold text-brand-navy">Kontak</li>
                </ol>
            </nav>
            <h1 class="mt-3 text-2xl font-bold text-brand-navy sm:text-3xl">Hubungi WP CCTV Indonesia</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">Konsultasi, permintaan survei, dan penawaran dilayani melalui WhatsApp pada jam operasional. Atau tinggalkan pesan lewat formulir — kami yang menghubungi Anda.</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[1fr_380px]">
            <div>
                @if(session('contact_submitted'))
                    <div class="mb-8 rounded-lg border border-green-200 bg-green-50 p-5" role="status">
                        <h2 class="text-sm font-bold text-green-800">Pesan terkirim</h2>
                        <p class="mt-1 text-sm leading-7 text-green-700">{{ session('status') }}</p>
                    </div>
                @elseif(session('status'))
                    <div class="mb-8 rounded-lg border border-green-200 bg-green-50 p-5" role="status">
                        <p class="text-sm leading-7 text-green-700">{{ session('status') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.store') }}" class="rounded-lg border border-slate-200 bg-white p-6 sm:p-8" aria-labelledby="form-kontak">
                    <h2 id="form-kontak" class="text-lg font-bold text-brand-navy">Formulir Permintaan Konsultasi</h2>
                    <p class="mt-1 text-sm text-slate-600">Isi singkat saja — kami hubungi Anda, bukan sebaliknya.</p>

                    @csrf

                    {{-- Honeypot: hidden from humans, filled by bots. --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="company_website">Situs perusahaan</label>
                        <input id="company_website" type="text" name="company_website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="customer_name" class="block text-sm font-semibold text-slate-700">Nama <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input id="customer_name" type="text" name="customer_name" value="{{ old('customer_name') }}" required autocomplete="name" class="mt-1.5 w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400" placeholder="Nama Anda">
                            @error('customer_name')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="whatsapp_number" class="block text-sm font-semibold text-slate-700">Nomor WhatsApp <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input id="whatsapp_number" type="tel" name="whatsapp_number" value="{{ old('whatsapp_number') }}" required autocomplete="tel" inputmode="tel" class="mt-1.5 w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400" placeholder="0812-xxxx-xxxx">
                            @error('whatsapp_number')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="lead_type" class="block text-sm font-semibold text-slate-700">Jenis kebutuhan</label>
                            <select id="lead_type" name="lead_type" class="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700">
                                <option value="consultation">Konsultasi umum</option>
                                <option value="installation">Instalasi baru</option>
                                <option value="repair">Perbaikan / servis</option>
                            </select>
                            @error('lead_type')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="requirements" class="block text-sm font-semibold text-slate-700">Kebutuhan Anda <span class="text-red-600" aria-hidden="true">*</span></label>
                            <textarea id="requirements" name="requirements" rows="5" required class="mt-1.5 w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400" placeholder="Contoh: butuh 4 kamera untuk rumah 2 lantai, 2 titik outdoor.">{{ old('requirements') }}</textarea>
                            @error('requirements')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="flex items-start gap-3 text-sm leading-6 text-slate-600">
                                <input type="checkbox" name="consent" value="1" required class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-blue">
                                <span>Saya setuju data di atas digunakan untuk menindaklanjuti permintaan ini. <a class="font-semibold text-brand-blue hover:underline" href="{{ route('pages.show', 'kebijakan-privasi') }}">Kebijakan privasi</a>.</span>
                            </label>
                            @error('consent')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <button type="submit" class="mt-6 inline-flex min-h-12 w-full items-center justify-center rounded-md bg-brand-blue px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 sm:w-auto">Kirim Permintaan</button>
                </form>
            </div>

            <aside class="space-y-6">
                <section class="rounded-lg border border-slate-200 bg-white p-6" aria-labelledby="kontak-langsung">
                    <h2 id="kontak-langsung" class="text-base font-bold text-brand-navy">Kontak Langsung</h2>
                    <ul class="mt-4 space-y-3 text-sm text-slate-600">
                        <li class="flex gap-3">
                            <span class="font-semibold text-brand-navy">WhatsApp:</span>
                            <a class="font-semibold text-brand-blue hover:underline" href="{{ $waHome }}" rel="noopener">{{ $site['whatsapp_display'] }}</a>
                        </li>
                        @if($site['email'] !== '')
                            <li class="flex gap-3">
                                <span class="font-semibold text-brand-navy">Email:</span>
                                <a class="hover:text-brand-blue" href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a>
                            </li>
                        @endif
                        @if($site['operating_hours'] !== '')
                            <li class="flex gap-3">
                                <span class="font-semibold text-brand-navy">Jam operasional:</span>
                                <span>{{ $site['operating_hours'] }}</span>
                            </li>
                        @endif
                        @if($site['address_locality'] !== '')
                            <li class="flex gap-3">
                                <span class="font-semibold text-brand-navy">Lokasi:</span>
                                <span>{{ $site['address_locality'] }}, {{ $site['address_region'] }}</span>
                            </li>
                        @endif
                    </ul>
                    <div class="mt-5">
                        @include('public.partials.whatsapp-cta', ['waUrl' => $waHome, 'waLabel' => 'Chat via WhatsApp'])
                    </div>
                </section>

                @if($site['service_areas'] !== [])
                    <section class="rounded-lg border border-slate-200 bg-white p-6" aria-labelledby="area-layanan-kontak">
                        <h2 id="area-layanan-kontak" class="text-base font-bold text-brand-navy">Area Layanan</h2>
                        <p class="mt-2 text-sm leading-7 text-slate-600">{{ implode(', ', $site['service_areas']) }}. Lokasi di luar daftar masih bisa didiskusikan.</p>
                    </section>
                @endif

                @if($faqs->isNotEmpty())
                    <section aria-labelledby="faq-kontak">
                        <h2 id="faq-kontak" class="text-base font-bold text-brand-navy">Pertanyaan Umum</h2>
                        <div class="mt-3">
                            @include('public.partials.faq-accordion', ['faqs' => $faqs->take(3)])
                        </div>
                    </section>
                @endif
            </aside>
        </div>
    </section>
@endsection
