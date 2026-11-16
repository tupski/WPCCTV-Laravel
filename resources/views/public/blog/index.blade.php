@extends('layouts.public')

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
                <ol class="flex flex-wrap gap-1.5">
                    <li><a class="hover:text-brand-blue" href="{{ route('home') }}">Beranda</a></li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" class="font-semibold text-brand-navy">Blog</li>
                </ol>
            </nav>
            <h1 class="mt-3 text-2xl font-bold text-brand-navy sm:text-3xl">{{ $heading }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">{{ $description }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[260px_1fr]">
            <aside class="space-y-6">
                <form method="GET" action="{{ route('blog.index') }}" class="rounded-lg border border-slate-200 bg-white p-5" aria-label="Cari artikel">
                    <label for="blog-search" class="block text-xs font-semibold text-slate-700">Cari artikel</label>
                    <input id="blog-search" type="search" name="q" value="{{ $search }}" placeholder="mis. kamera outdoor" class="mt-1.5 w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400">
                    @if($activeCategory)
                        <input type="hidden" name="category" value="{{ $activeCategory->slug }}">
                    @endif
                    <button type="submit" class="mt-3 inline-flex min-h-11 w-full items-center justify-center rounded-md bg-brand-blue px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Cari</button>
                </form>

                @if($categories->isNotEmpty())
                    <nav class="rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="kategori-blog">
                        <h2 id="kategori-blog" class="text-sm font-bold text-brand-navy">Kategori</h2>
                        <ul class="mt-3 space-y-1.5 text-sm">
                            <li><a class="block rounded-md px-2 py-2 {{ $activeCategory === null ? 'bg-brand-light font-semibold text-brand-navy' : 'text-slate-600 hover:bg-slate-100' }}" href="{{ route('blog.index') }}">Semua artikel</a></li>
                            @foreach($categories as $category)
                                <li><a class="block rounded-md px-2 py-2 {{ $activeCategory?->is($category) ? 'bg-brand-light font-semibold text-brand-navy' : 'text-slate-600 hover:bg-slate-100' }}" href="{{ route('blog.index', ['category' => $category->slug]) }}">{{ $category->name }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                @if($tags->isNotEmpty())
                    <nav class="rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="tag-blog">
                        <h2 id="tag-blog" class="text-sm font-bold text-brand-navy">Tag Populer</h2>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                <li><a class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:border-brand-blue hover:text-brand-blue" href="{{ route('blog.index', ['tag' => $tag->slug]) }}">#{{ $tag->name }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            </aside>

            <div>
                @if($articles->isNotEmpty())
                    <p class="text-sm text-slate-500" role="status">{{ $articles->total() }} artikel</p>
                    <div class="mt-5 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($articles as $article)
                            @include('public.partials.article-card', ['article' => $article])
                        @endforeach
                    </div>
                    {{ $articles->links('pagination::tailwind') }}
                @else
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 class="text-base font-semibold text-brand-navy">Tidak ada artikel ditemukan</h2>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-slate-600">Coba kata kunci lain atau lihat semua artikel.</p>
                        <a href="{{ route('blog.index') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Lihat semua artikel</a>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
