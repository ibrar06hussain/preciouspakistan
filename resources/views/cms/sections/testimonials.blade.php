<section class="{{ $styleClass }}">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <x-ui.section-heading
            :eyebrow="$config['eyebrow'] ?? null"
            :title="$config['heading'] ?? 'Traveler Perspectives'"
        />

        <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-3">
            @foreach ($config['items'] ?? [] as $item)
                <x-ui.card class="flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        @if ($item['is_sample'] ?? true)
                            <x-ui.badge tone="demo">Sample Testimonial</x-ui.badge>
                        @endif
                        <blockquote class="text-sm leading-relaxed text-slate-700">&ldquo;{{ $item['quote'] ?? '' }}&rdquo;</blockquote>
                    </div>
                    <div class="border-t border-slate-100 pt-3">
                        <p class="text-sm font-bold text-slate-900">{{ $item['author_name'] ?? '' }}</p>
                        <p class="text-xs text-slate-600">{{ $item['author_origin'] ?? '' }} @if (filled($item['trip_context'] ?? null)) &bull; {{ $item['trip_context'] }} @endif</p>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    </div>
</section>
