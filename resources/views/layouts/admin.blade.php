@extends('layouts.document')

@section('body')
    <div class="min-h-screen bg-white">
        <header class="border-b border-slate-200">
            <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="text-base font-bold text-brand-navy">WP CCTV Indonesia</a>
                <span class="text-sm text-slate-600">Area administrasi</span>
            </div>
        </header>
        <main id="main-content" class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-brand-navy">@yield('page_title')</h1>
            <p class="mt-3 max-w-2xl text-base leading-7 text-slate-700">@yield('content')</p>
        </main>
    </div>
@endsection
