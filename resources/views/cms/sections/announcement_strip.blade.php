<section class="bg-amber-100 border-y border-amber-200 text-amber-950">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-3 px-4 py-3 text-sm font-medium sm:px-6 lg:px-8">
        <span>{{ $config['text'] ?? '' }}</span>
        @if (filled($config['link_label'] ?? null) && filled($config['link_href'] ?? null))
            <a href="{{ $config['link_href'] }}" class="font-bold underline hover:text-amber-800">
                {{ $config['link_label'] }}
            </a>
        @endif
    </div>
</section>
