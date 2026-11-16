@extends('layouts.document')

@php
    $site = \App\Support\SiteInfo::all();
    $siteName = $site['name'];
    $waHome = \App\Support\WhatsAppLink::forHome();

    // Full nav entries; a section is hidden when it has no published content,
    // so the header never links to an empty catalog.
    $navItems = array_values(array_filter([
        ['label' => 'Beranda', 'url' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'Produk', 'url' => route('products.index'), 'active' => request()->routeIs('products.*')],
        ['label' => 'Paket CCTV', 'url' => route('packages.index'), 'active' => request()->routeIs('packages.*')],
        ['label' => 'Layanan', 'url' => route('services.index'), 'active' => request()->routeIs('services.*')],
        ['label' => 'Blog', 'url' => route('blog.index'), 'active' => request()->routeIs('blog.*')],
        ['label' => 'FAQ', 'url' => route('faqs.index'), 'active' => request()->routeIs('faqs.*')],
        ['label' => 'Kontak', 'url' => route('contact.show'), 'active' => request()->routeIs('contact.*')],
    ]));
@endphp

@section('body')
    <div class="flex min-h-screen flex-col">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-3 focus:text-sm focus:font-semibold focus:text-brand-navy focus:shadow-lg">Lewati ke konten utama</a>

        {{-- data-controller lives on the header (not the inner container) so the
             mobile menu below stays inside the controller scope: a Stimulus
             target outside its controller element is invisible to the
             controller and connect() throws. --}}
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur" data-controller="navigation">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5" aria-label="{{ $siteName }}, kembali ke beranda">
                    <span class="flex h-9 w-9 items-center justify-center rounded-md bg-brand-navy text-sm font-bold text-white" aria-hidden="true">WP</span>
                    <span class="leading-tight">
                        <span class="block text-sm font-bold text-brand-navy sm:text-base">{{ $siteName }}</span>
                        <span class="block text-[10px] font-semibold uppercase tracking-widest text-brand-blue">{{ $site['tagline'] }}</span>
                    </span>
                </a>

                <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigasi utama">
                    @foreach($navItems as $item)
                        <a
                            href="{{ $item['url'] }}"
                            class="rounded-md px-3 py-2 text-sm font-medium {{ $item['active'] ? 'bg-brand-light text-brand-navy' : 'text-slate-600 hover:bg-slate-100 hover:text-brand-navy' }}"
                            @if($item['active']) aria-current="page" @endif
                        >{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2">
                    <a
                        href="{{ $waHome }}"
                        class="inline-flex min-h-11 items-center gap-2 rounded-md bg-[#25D366] px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1fb855] sm:px-4"
                        rel="noopener"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <span class="hidden sm:inline">Hubungi Kami</span>
                        <span class="sm:hidden">WhatsApp</span>
                    </a>

                    <button
                        type="button"
                        class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-md border border-slate-300 text-brand-navy lg:hidden"
                        aria-expanded="false"
                        aria-controls="public-navigation"
                        aria-label="Buka menu navigasi"
                        data-navigation-target="toggle"
                        data-action="navigation#toggle"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>

            <nav
                id="public-navigation"
                class="border-t border-slate-200 bg-white px-4 pb-4 pt-2 lg:hidden"
                aria-label="Navigasi utama"
                data-navigation-target="menu"
                hidden
            >
                @foreach($navItems as $item)
                    <a
                        href="{{ $item['url'] }}"
                        class="flex min-h-11 items-center rounded-md px-3 text-sm font-medium {{ $item['active'] ? 'bg-brand-light text-brand-navy' : 'text-slate-700 hover:bg-slate-100' }}"
                        data-action="click->navigation#close"
                        @if($item['active']) aria-current="page" @endif
                    >{{ $item['label'] }}</a>
                @endforeach
            </nav>
        </header>

        <main id="main-content" class="flex-1">
            @yield('content')
        </main>

        <footer class="mt-16 border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="text-base font-bold text-brand-navy">{{ $siteName }}</p>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-widest text-brand-blue">{{ $site['tagline'] }}</p>
                        <p class="mt-4 max-w-xs text-sm leading-6 text-slate-600">Jasa survei, instalasi, dan perbaikan sistem CCTV untuk rumah, toko, kantor, gudang, sekolah, dan pabrik.</p>
                    </div>

                    <nav aria-label="Tautan layanan">
                        <p class="text-sm font-semibold text-brand-navy">Layanan</p>
                        <ul class="mt-3 space-y-2 text-sm text-slate-600">
                            <li><a class="hover:text-brand-blue" href="{{ route('packages.index') }}">Paket Instalasi CCTV</a></li>
                            <li><a class="hover:text-brand-blue" href="{{ route('services.index') }}">Semua Layanan</a></li>
                            <li><a class="hover:text-brand-blue" href="{{ route('products.index') }}">Katalog Produk</a></li>
                            <li><a class="hover:text-brand-blue" href="{{ route('blog.index') }}">Blog & Panduan</a></li>
                        </ul>
                    </nav>

                    <nav aria-label="Tautan informasi">
                        <p class="text-sm font-semibold text-brand-navy">Informasi</p>
                        <ul class="mt-3 space-y-2 text-sm text-slate-600">
                            <li><a class="hover:text-brand-blue" href="{{ route('pages.show', 'tentang-kami') }}">Tentang Kami</a></li>
                            <li><a class="hover:text-brand-blue" href="{{ route('pages.show', 'garansi') }}">Garansi & Purnajual</a></li>
                            <li><a class="hover:text-brand-blue" href="{{ route('pages.show', 'area-layanan') }}">Area Layanan</a></li>
                            <li><a class="hover:text-brand-blue" href="{{ route('pages.show', 'kebijakan-privasi') }}">Kebijakan Privasi</a></li>
                            <li><a class="hover:text-brand-blue" href="{{ route('pages.show', 'syarat-ketentuan') }}">Syarat & Ketentuan</a></li>
                        </ul>
                    </nav>

                    <div>
                        <p class="text-sm font-semibold text-brand-navy">Kontak</p>
                        <ul class="mt-3 space-y-2 text-sm text-slate-600">
                            <li>WhatsApp: <a class="font-semibold text-brand-blue hover:underline" href="{{ $waHome }}" rel="noopener">{{ $site['whatsapp_display'] }}</a></li>
                            @if($site['email'] !== '')
                                <li>Email: <a class="hover:text-brand-blue" href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a></li>
                            @endif
                            @if($site['operating_hours'] !== '')
                                <li>Jam operasional: {{ $site['operating_hours'] }}</li>
                            @endif
                            @if($site['address_locality'] !== '')
                                <li>Wilayah: {{ $site['address_locality'] }} dan sekitarnya</li>
                            @endif
                        </ul>
                    </div>
                </div>

                <div class="mt-10 border-t border-slate-200 pt-6 text-xs text-slate-500 sm:flex sm:items-center sm:justify-between">
                    <p>&copy; {{ date('Y') }} {{ $siteName }}. Situs ini tidak menerima transaksi pembayaran online.</p>
                    <p class="mt-2 sm:mt-0">Konsultasi dan pemesanan dilayani melalui WhatsApp.</p>
                </div>
            </div>
        </footer>

        @stack('scripts')
    </div>
@endsection
