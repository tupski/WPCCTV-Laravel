@extends('layouts.public')

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
                <ol class="flex flex-wrap gap-1.5">
                    <li><a class="hover:text-brand-blue" href="{{ route('home') }}">Beranda</a></li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" class="font-semibold text-brand-navy">FAQ</li>
                </ol>
            </nav>
            <h1 class="mt-3 text-2xl font-bold text-brand-navy sm:text-3xl">Pertanyaan yang Sering Diajukan</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">Jawaban singkat untuk pertanyaan umum seputar instalasi, perbaikan, garansi, dan perawatan CCTV. Pertanyaan lain bisa langsung ditanyakan via WhatsApp.</p>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        @if($faqs->isNotEmpty())
            @include('public.partials.faq-accordion', ['faqs' => $faqs, 'accordionId' => 'faq-semua'])
        @else
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                <h2 class="text-base font-semibold text-brand-navy">Belum ada pertanyaan yang dipublikasikan</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-slate-600">Silakan tanyakan langsung kebutuhan Anda melalui WhatsApp.</p>
                <div class="mt-5 flex justify-center">@include('public.partials.whatsapp-cta')</div>
            </div>
        @endif

        <div class="mt-10 rounded-lg bg-brand-light p-6 text-center">
            <h2 class="text-base font-bold text-brand-navy">Tidak menemukan jawaban Anda?</h2>
            <div class="mt-4 flex justify-center">
                @include('public.partials.whatsapp-cta', ['waLabel' => 'Tanya Lebih Lanjut'])
            </div>
        </div>
    </section>
@endsection
