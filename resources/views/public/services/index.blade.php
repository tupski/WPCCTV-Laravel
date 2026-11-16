@extends('layouts.public')

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
                <ol class="flex flex-wrap gap-1.5">
                    <li><a class="hover:text-brand-blue" href="{{ route('home') }}">Beranda</a></li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" class="font-semibold text-brand-navy">Layanan</li>
                </ol>
            </nav>
            <h1 class="mt-3 text-2xl font-bold text-brand-navy sm:text-3xl">Layanan CCTV</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">Dari survei lokasi sampai perawatan berkala — semua layanan dikerjakan teknisi berpengalaman dengan penawaran tertulis di muka.</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if($services->isNotEmpty())
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($services as $service)
                    <article class="flex h-full flex-col rounded-lg border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                        <h2 class="text-base font-bold text-brand-navy">
                            <a class="hover:text-brand-blue" href="{{ route('services.show', $service->slug) }}">{{ $service->title }}</a>
                        </h2>
                        @if($service->description)
                            <p class="mt-2 line-clamp-3 text-sm leading-7 text-slate-600">{{ $service->description }}</p>
                        @endif
                        <div class="mt-auto flex items-center gap-3 pt-4">
                            <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-md border border-slate-300 px-4 py-2.5 text-sm font-semibold text-brand-navy hover:bg-slate-50" href="{{ route('services.show', $service->slug) }}">Detail layanan</a>
                            @include('public.partials.whatsapp-cta', [
                                'waUrl' => \App\Support\WhatsAppLink::forService($service->slug),
                                'waLabel' => $service->type === 'repair' ? 'Konsultasi Servis' : 'Konsultasi',
                            ])
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                <h2 class="text-base font-semibold text-brand-navy">Daftar layanan sedang disiapkan</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-slate-600">Silakan hubungi kami langsung untuk konsultasi kebutuhan CCTV Anda.</p>
                <div class="mt-5 flex justify-center">@include('public.partials.whatsapp-cta')</div>
            </div>
        @endif
    </section>
@endsection
