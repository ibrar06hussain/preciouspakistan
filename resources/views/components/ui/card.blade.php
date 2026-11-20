@props([
    'padded' => true,
    'dark' => false,
])

<div {{ $attributes->merge([
    'class' => 'rounded-2xl border shadow-sm '
        .($dark ? 'border-emerald-900 bg-emerald-950 text-white ' : 'border-slate-200/80 bg-white ')
        .($padded ? 'p-6' : ''),
]) }}>
    {{ $slot }}
</div>
