@extends('layouts.public')

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
            <ol class="flex flex-wrap gap-1.5">
                <li><a class="hover:text-brand-blue" href="{{ route('home') }}">Beranda</a></li>
                <li aria-hidden="true">/</li>
                <li><a class="hover:text-brand-blue" href="{{ route('blog.index') }}">Blog</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="font-semibold text-brand-navy">{{ $article->title }}</li>
            </ol>
        </nav>

        <header class="mt-6">
            @if($article->category !== null)
                <a class="text-xs font-semibold uppercase tracking-wide text-brand-blue hover:underline" href="{{ route('blog.index', ['category' => $article->category->slug]) }}">{{ $article->category->name }}</a>
            @endif
            <h1 class="mt-2 text-2xl font-bold leading-tight text-brand-navy sm:text-3xl lg:text-4xl">{{ $article->title }}</h1>
            @if($article->excerpt)
                <p class="mt-4 text-base leading-7 text-slate-600">{{ $article->excerpt }}</p>
            @endif
            <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-slate-200 pb-5 text-xs text-slate-500">
                @if($article->author !== null)
                    <span>Oleh {{ $article->author->name }}</span>
                @endif
                <span>Dipublikasikan {{ \App\Support\Format::date($article->published_at) }}</span>
                @if($article->reading_time > 0)
                    <span>{{ $article->reading_time }} menit baca</span>
                @endif
            </div>
        </header>

        <div class="prose-slate mt-8 space-y-4 text-[15px] leading-8 text-slate-700">
            {!! \App\Support\Prose::toHtml($article->body) !!}
        </div>

        @if($article->tags->isNotEmpty())
            <div class="mt-8 border-t border-slate-200 pt-6">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tag</h2>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach($article->tags as $tag)
                        <li><a class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:border-brand-blue hover:text-brand-blue" href="{{ route('blog.index', ['tag' => $tag->slug]) }}">#{{ $tag->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-10 rounded-lg bg-brand-light p-6">
            <h2 class="text-base font-bold text-brand-navy">Masih ada pertanyaan?</h2>
            <p class="mt-2 text-sm leading-7 text-slate-600">Tanyakan langsung ke tim kami lewat WhatsApp — gratis, dijawab pada jam operasional.</p>
            <div class="mt-4">
                @include('public.partials.whatsapp-cta', [
                    'waUrl' => \App\Support\WhatsAppLink::forArticle($article->slug),
                    'waLabel' => 'Tanya Lebih Lanjut',
                ])
            </div>
        </div>

        @if($relatedArticles->isNotEmpty())
            <section class="mt-12" aria-labelledby="artikel-terkait">
                <h2 id="artikel-terkait" class="text-xl font-bold text-brand-navy">Artikel Terkait</h2>
                <div class="mt-6 grid gap-6 sm:grid-cols-3">
                    @foreach($relatedArticles as $related)
                        @include('public.partials.article-card', ['article' => $related])
                    @endforeach
                </div>
            </section>
        @endif
    </article>
@endsection
