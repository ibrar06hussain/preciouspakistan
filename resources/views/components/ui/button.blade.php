@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-semibold rounded-lg transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer';

    $variants = [
        'primary' => 'bg-emerald-800 text-white hover:bg-emerald-900 shadow-sm',
        'accent' => 'bg-amber-600 text-white hover:bg-amber-700 shadow-sm',
        'secondary' => 'bg-slate-900 text-white hover:bg-slate-800 shadow-sm',
        'outline' => 'border border-slate-300 bg-white text-slate-900 hover:bg-slate-50',
        'outline-light' => 'border border-white/40 bg-white/10 text-white hover:bg-white/20',
        'ghost' => 'text-slate-700 hover:bg-slate-100 hover:text-slate-900',
    ];

    $sizes = [
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-6 py-3.5 text-base',
    ];

    $classes = trim($base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
