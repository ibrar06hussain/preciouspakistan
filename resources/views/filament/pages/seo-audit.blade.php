<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <div class="text-xs uppercase text-gray-500">Indexable Sitemap URLs</div>
            <div class="mt-2 text-2xl font-bold">{{ $sitemap_count }}</div>
        </div>
        <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <div class="text-xs uppercase text-gray-500">Missing SEO Titles</div>
            <div class="mt-2 text-2xl font-bold">{{ $audit['missing_seo_titles'] }}</div>
        </div>
        <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <div class="text-xs uppercase text-gray-500">Missing Meta Descriptions</div>
            <div class="mt-2 text-2xl font-bold">{{ $audit['missing_seo_descriptions'] }}</div>
        </div>
        <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <div class="text-xs uppercase text-gray-500">Media Missing Alt Text</div>
            <div class="mt-2 text-2xl font-bold">{{ $audit['missing_media_alt'] }}</div>
        </div>
    </div>

    <div class="mt-6 p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 space-y-2 text-sm">
        <div><strong>Sitemap XML:</strong> <a href="{{ route('seo.sitemap') }}" target="_blank" class="text-emerald-600 underline">{{ route('seo.sitemap') }}</a></div>
        <div><strong>Robots.txt:</strong> <a href="{{ route('seo.robots') }}" target="_blank" class="text-emerald-600 underline">{{ route('seo.robots') }}</a></div>
        <div><strong>Duplicate Titles Detected:</strong> {{ count($audit['duplicate_titles']) === 0 ? 'None' : implode(', ', $audit['duplicate_titles']) }}</div>
        <div class="text-xs text-gray-500">Structured data JSON-LD strictly uses truthful database facts (no fabricated review stars or ratings).</div>
    </div>
</x-filament-panels::page>
