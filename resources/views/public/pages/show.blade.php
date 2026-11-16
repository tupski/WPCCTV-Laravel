@extends('layouts.public')

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
            <ol class="flex flex-wrap gap-1.5">
                <li><a class="hover:text-brand-blue" href="{{ route('home') }}">Beranda</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="font-semibold text-brand-navy">{{ $page->title }}</li>
            </ol>
        </nav>

        <h1 class="mt-6 text-2xl font-bold text-brand-navy sm:text-3xl">{{ $page->title }}</h1>

        <div class="prose-slate mt-8 space-y-4 text-[15px] leading-8 text-slate-700">
            {!! \App\Support\Prose::toHtml($page->body) !!}
        </div>

        @if($faqs->isNotEmpty())
            <section class="mt-12" aria-labelledby="faq-halaman">
                <h2 id="faq-halaman" class="text-xl font-bold text-brand-navy">Pertanyaan Terkait</h2>
                <div class="mt-4">
                    @include('public.partials.faq-accordion', ['faqs' => $faqs])
                </div>
            </section>
        @endif

        <div class="mt-12 rounded-lg bg-brand-light p-6">
            <h2 class="text-base font-bold text-brand-navy">Butuh bantuan lebih lanjut?</h2>
            <div class="mt-4">
                @include('public.partials.whatsapp-cta', [
                    'waUrl' => \App\Support\WhatsAppLink::forPage($page->slug),
                    'waLabel' => 'Hubungi Kami',
                ])
            </div>
        </div>
    </article>
@endsection
