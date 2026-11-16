@extends('layouts.document')

@php
    // Nav entries carry their gate so the markup cannot drift from the route
    // middleware. A role that cannot reach a screen never sees its link.
    $navigation = array_values(array_filter([
        [
            'label' => 'Dasbor',
            'route' => 'admin.dashboard',
            'active' => 'admin.dashboard',
            'gate' => 'admin',
        ],
        [
            'label' => 'Lead',
            'route' => 'admin.leads-ui.index',
            'active' => 'admin.leads-ui.*',
            'gate' => 'manage-leads',
        ],
        [
            'label' => 'Analitik',
            'route' => 'admin.analytics-ui.index',
            'active' => 'admin.analytics-ui.*',
            'gate' => 'view-analytics',
        ],
        [
            'label' => 'Log aktivitas',
            'route' => 'admin.activity-logs-ui.index',
            'active' => 'admin.activity-logs-ui.*',
            'gate' => 'view-activity-logs',
        ],
        [
            'label' => 'Pengaturan',
            'route' => 'admin.settings-ui.edit',
            'active' => 'admin.settings-ui.*',
            'gate' => 'manage-settings',
        ],
        [
            'label' => 'Notifikasi',
            'route' => 'admin.push-settings.index',
            'active' => 'admin.push-settings.*',
            'gate' => 'admin',
        ],
    ], fn (array $item): bool => auth()->check() && auth()->user()->can($item['gate'])));
@endphp

@section('body')
    <div class="min-h-screen bg-white">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-3 focus:text-brand-navy">Lewati ke konten</a>

        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8" data-controller="navigation">
                <a href="{{ route('admin.home') }}" class="text-base font-bold text-brand-navy">WP CCTV Indonesia</a>

                <button
                    type="button"
                    class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-md border border-slate-300 px-3 text-sm font-semibold text-brand-navy lg:hidden"
                    aria-expanded="false"
                    aria-controls="admin-navigation"
                    aria-label="Buka menu administrasi"
                    data-navigation-target="toggle"
                    data-action="navigation#toggle"
                >Menu</button>

                <div class="hidden items-center gap-4 lg:flex">
                    <span class="text-sm text-slate-600">{{ auth()->user()?->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="min-h-11 rounded-md border border-slate-300 px-3 text-sm font-semibold text-brand-navy">Keluar</button>
                    </form>
                </div>
            </div>

            <nav
                id="admin-navigation"
                class="border-t border-slate-200 px-4 py-2 lg:hidden"
                aria-label="Navigasi administrasi"
                data-navigation-target="menu"
                hidden
            >
                @foreach($navigation as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        class="flex min-h-11 items-center text-sm font-medium {{ request()->routeIs($item['active']) ? 'text-brand-blue' : 'text-brand-navy' }}"
                        data-action="click->navigation#close"
                        @if(request()->routeIs($item['active'])) aria-current="page" @endif
                    >{{ $item['label'] }}</a>
                @endforeach
                <div class="flex items-center justify-between gap-4 border-t border-slate-200 py-2">
                    <span class="text-sm text-slate-600">{{ auth()->user()?->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="min-h-11 rounded-md border border-slate-300 px-3 text-sm font-semibold text-brand-navy">Keluar</button>
                    </form>
                </div>
            </nav>
        </header>

        <div class="mx-auto flex max-w-7xl gap-10 px-4 py-8 sm:px-6 lg:px-8">
            <nav class="hidden w-48 shrink-0 lg:block" aria-label="Navigasi administrasi">
                <ul class="space-y-1">
                    @foreach($navigation as $item)
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                class="flex min-h-10 items-center rounded-md px-3 text-sm font-medium {{ request()->routeIs($item['active']) ? 'bg-slate-100 text-brand-navy' : 'text-slate-600 hover:text-brand-navy' }}"
                                @if(request()->routeIs($item['active'])) aria-current="page" @endif
                            >{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <main id="main-content" class="min-w-0 flex-1">
                <div class="flex flex-wrap items-baseline justify-between gap-4 border-b border-slate-200 pb-4">
                    <div>
                        <h1 class="text-xl font-semibold text-brand-navy">@yield('page_title')</h1>
                        @hasSection('page_subtitle')
                            <p class="mt-1 text-sm text-slate-600">@yield('page_subtitle')</p>
                        @endif
                    </div>
                    @hasSection('page_actions')
                        <div class="flex flex-wrap items-center gap-2">@yield('page_actions')</div>
                    @endif
                </div>

                @include('admin.partials.flash')

                <div class="pt-6">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
@endsection
