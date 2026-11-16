@php
    // Tones are semantic: success = deal, danger = lost, info = qualified.
    // Colour never carries the message alone — the badge always prints text.
    $tones = [
        'neutral' => 'border-slate-300 text-slate-700',
        'info' => 'border-brand-blue text-brand-blue',
        'success' => 'border-emerald-700 text-emerald-800',
        'danger' => 'border-red-700 text-red-800',
        'muted' => 'border-slate-300 text-slate-500',
    ];
    $tone = $tones[$tone ?? 'neutral'] ?? $tones['neutral'];
@endphp
<span class="inline-block whitespace-nowrap rounded-md border px-2 py-0.5 text-xs font-medium {{ $tone }}">{{ $label }}</span>
