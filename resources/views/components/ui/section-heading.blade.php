@props([
    'eyebrow' => null,
    'title',
    'subtitle' => null,
    'align' => 'left',
])

<div class="{{ $align === 'center' ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl' }} space-y-3">
    @if ($eyebrow)
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-800">{{ $eyebrow }}</p>
    @endif

    <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">
        {{ $title }}
    </h2>

    @if ($subtitle)
        <p class="text-base leading-relaxed text-slate-600 sm:text-lg">
            {{ $subtitle }}
        </p>
    @endif
</div>
