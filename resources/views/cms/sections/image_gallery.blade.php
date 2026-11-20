<section class="{{ $styleClass }}">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <x-ui.section-heading
            :eyebrow="$config['eyebrow'] ?? null"
            :title="$config['heading'] ?? ''"
            :subtitle="$config['subtitle'] ?? null"
        />

        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($config['images'] ?? [] as $image)
                <figure class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    @if (filled($image['url'] ?? null))
                        <img src="{{ $image['url'] }}" alt="{{ $image['alt'] ?? 'Precious Pakistan gallery image' }}" loading="lazy" class="h-56 w-full object-cover">
                    @else
                        <div class="flex h-48 items-center justify-center bg-gradient-to-br from-emerald-900 to-slate-900 p-4 text-center text-xs font-semibold text-emerald-100">
                            {{ $image['alt'] ?? 'Sample Media Preview' }}
                        </div>
                    @endif
                    <figcaption class="flex items-center justify-between gap-2 px-4 py-3 text-xs text-slate-700">
                        <span>{{ $image['caption'] ?? $image['alt'] ?? '' }}</span>
                        @if ($image['is_sample'] ?? false)
                            <x-ui.badge tone="demo">Sample</x-ui.badge>
                        @endif
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
