@extends('layouts.document')

@section('body')
    <div class="min-h-screen">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded-sm focus:bg-white focus:px-4 focus:py-3 focus:text-brand-navy">Lewati ke konten</a>
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8" data-controller="navigation">
                <a class="text-base font-bold text-brand-navy" href="{{ route('home') }}" aria-label="WP CCTV Indonesia, beranda">WP CCTV Indonesia</a>
                <button class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-sm border border-slate-300 px-3 text-sm font-semibold text-brand-navy md:hidden" type="button" aria-expanded="false" aria-controls="public-navigation" aria-label="Buka navigasi" data-navigation-target="toggle" data-action="navigation#toggle">Menu</button>
                <nav id="public-navigation" class="absolute left-0 right-0 top-[65px] z-10 border-b border-slate-200 bg-white px-4 py-3 md:static md:block md:border-0 md:p-0" aria-label="Navigasi utama" data-navigation-target="menu" hidden>
                    <a class="inline-flex min-h-11 items-center px-3 text-sm font-medium text-brand-navy hover:text-brand-blue" href="{{ route('home') }}" data-action="click->navigation#close">Beranda</a>
                </nav>
                <span class="hidden text-sm text-slate-600 md:inline">SMART SECURITY SOLUTIONS</span>
            </div>
        </header>
        <main id="main-content" class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @yield('content')
        </main>
        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-5 text-sm text-slate-600 sm:px-6 lg:px-8">WP CCTV Indonesia</div>
        </footer>
    </div>
@endsection
