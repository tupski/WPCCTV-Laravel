@php
    // Accordion built on native <details>/<summary>: keyboard operable and
    // collapsible without JavaScript, so it survives Turbo navigation with no
    // lifecycle code at all.
    $accordionId = $accordionId ?? 'accordion-'.uniqid();
@endphp
<div class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white">
    @foreach($faqs as $index => $faq)
        <details class="group" id="{{ $accordionId }}-{{ $index }}" @if($index === 0) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-left text-sm font-semibold text-brand-navy transition hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
                <span>{{ $faq->question }}</span>
                <svg class="h-4 w-4 shrink-0 text-slate-500 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </summary>
            <div class="px-5 pb-5 text-sm leading-7 text-slate-600">
                {{ $faq->answer }}
            </div>
        </details>
    @endforeach
</div>
