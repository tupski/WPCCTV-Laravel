@php
    // Product card is a technical reference, not merchandise: brand, model,
    // key spec, detail link. No price badge, no cart.
    $keySpec = is_iterable($product->key_features) && count($product->key_features) > 0
        ? $product->key_features[0]
        : null;
@endphp
<article class="flex h-full flex-col rounded-lg border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
    <div class="p-5">
        @if($product->brand !== null)
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $product->brand->name }}</p>
        @endif
        <h3 class="mt-1 text-base font-bold text-brand-navy">
            <a class="hover:text-brand-blue" href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
        </h3>
        @if($product->model)
            <p class="mt-0.5 text-xs text-slate-500">Model: {{ $product->model }}</p>
        @endif

        @if($product->camera_type)
            <span class="mt-3 inline-block rounded-full bg-brand-light px-2.5 py-0.5 text-xs font-semibold text-brand-navy">{{ ucfirst($product->camera_type) }}</span>
        @endif

        @if($keySpec)
            <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600">{{ $keySpec }}</p>
        @endif
    </div>

    <div class="mt-auto flex items-center gap-3 border-t border-slate-100 p-5">
        <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-md border border-slate-300 px-4 py-2.5 text-sm font-semibold text-brand-navy transition hover:bg-slate-50" href="{{ route('products.show', $product->slug) }}">Detail produk</a>
        @include('public.partials.whatsapp-cta', [
            'waUrl' => \App\Support\WhatsAppLink::forProduct($product->slug),
            'waLabel' => 'Konsultasi',
        ])
    </div>
</article>
