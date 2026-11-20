@props([
    'tone' => 'emerald',
])

@php
    $tones = [
        'emerald' => 'bg-emerald-100 text-emerald-900 border-emerald-200',
        'amber' => 'bg-amber-100 text-amber-900 border-amber-200',
        'slate' => 'bg-slate-100 text-slate-800 border-slate-200',
        'demo' => 'bg-amber-50 text-amber-900 border-amber-300',
        'dark' => 'bg-white/15 text-emerald-50 border-white/20',
    ];

    $classes = 'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold tracking-wide '.($tones[$tone] ?? $tones['emerald']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
