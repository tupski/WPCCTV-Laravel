@php
    // Article card: category, title, excerpt, date, reading time.
@endphp
<article class="flex h-full flex-col rounded-lg border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
    <div class="p-5">
        @if($article->category !== null)
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-blue">{{ $article->category->name }}</p>
        @endif
        <h3 class="mt-1.5 text-base font-bold leading-snug text-brand-navy">
            <a class="hover:text-brand-blue" href="{{ route('blog.show', $article->slug) }}">{{ $article->title }}</a>
        </h3>
        @if($article->excerpt)
            <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $article->excerpt }}</p>
        @endif
    </div>
    <div class="mt-auto flex items-center justify-between border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
        <span>{{ \App\Support\Format::date($article->published_at) }}</span>
        @if($article->reading_time > 0)
            <span>{{ $article->reading_time }} menit baca</span>
        @endif
    </div>
</article>
