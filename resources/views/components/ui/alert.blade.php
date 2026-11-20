@props([
    'type' => 'info',
])

@php
    $styles = [
        'success' => 'border-emerald-300 bg-emerald-50 text-emerald-950',
        'error' => 'border-red-300 bg-red-50 text-red-950',
        'warning' => 'border-amber-300 bg-amber-50 text-amber-950',
        'info' => 'border-slate-300 bg-slate-50 text-slate-900',
    ];
@endphp

<div role="status" {{ $attributes->merge(['class' => 'rounded-xl border px-4 py-3 text-sm font-medium '.($styles[$type] ?? $styles['info'])]) }}>
    {{ $slot }}
</div>
