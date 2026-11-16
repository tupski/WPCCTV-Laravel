@php
    // Package card communicates a service offer, never a merchandise listing:
    // no cart, no buy button, only "Lihat detail".
    $usageLabel = $package->usageCategories->pluck('name')->take(2)->implode(', ');
@endphp
<article class="flex h-full flex-col rounded-lg border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
    <div class="flex items-start justify-between gap-3 border-b border-slate-100 p-5">
        <div>
            <h3 class="text-base font-bold text-brand-navy">
                <a class="hover:text-brand-blue" href="{{ route('packages.show', $package->slug) }}">{{ $package->title }}</a>
            </h3>
            @if($package->camera_quantity)
                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-brand-blue">{{ $package->camera_quantity }} kamera</p>
            @endif
        </div>
        @if($package->price !== null)
            <p class="whitespace-nowrap rounded-md bg-brand-light px-2.5 py-1 text-sm font-bold text-brand-navy">
                Mulai Rp{{ number_format((float) $package->price, 0, ',', '.') }}
            </p>
        @endif
    </div>

    <div class="flex-1 p-5">
        <p class="line-clamp-3 text-sm leading-6 text-slate-600">{{ $package->description }}</p>

        @if($usageLabel !== '')
            <p class="mt-3 text-xs text-slate-500">Untuk: {{ $usageLabel }}</p>
        @endif

        @if(is_iterable($package->included_equipment) && count($package->included_equipment) > 0)
            <ul class="mt-3 space-y-1.5 text-sm text-slate-600">
                @foreach(array_slice($package->included_equipment, 0, 3) as $item)
                    <li class="flex gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>{{ $item }}</span></li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="flex items-center gap-3 border-t border-slate-100 p-5">
        <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-md border border-brand-blue px-4 py-2.5 text-sm font-semibold text-brand-blue transition hover:bg-brand-light" href="{{ route('packages.show', $package->slug) }}">Lihat detail</a>
        @include('public.partials.whatsapp-cta', [
            'waUrl' => \App\Support\WhatsAppLink::forPackage($package->slug),
            'waLabel' => 'Konsultasikan Paket Ini',
        ])
    </div>
</article>
