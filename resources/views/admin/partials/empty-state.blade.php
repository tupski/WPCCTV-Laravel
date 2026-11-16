@php
    // One sentence telling the operator what to do next. Never "Belum ada data."
    $actionUrl = $actionUrl ?? null;
    $actionLabel = $actionLabel ?? null;
@endphp
<div class="border-l-2 border-slate-300 py-1 pl-4">
    <p class="text-sm text-slate-700">{{ $message }}</p>
    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="mt-2 inline-flex min-h-10 items-center text-sm font-semibold text-brand-blue">{{ $actionLabel }}</a>
    @endif
</div>
