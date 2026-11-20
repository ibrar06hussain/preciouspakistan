<?php

namespace App\Modules\Seo\Services;

use App\Modules\Cms\Models\CmsPage;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\Region;
use App\Modules\Media\Models\MediaAsset;
use App\Modules\Tours\Models\Tour;

/**
 * Truthful Tourism SEO Engine:
 * - Generates valid JSON-LD ONLY from real database facts (NEVER fabricates fake aggregateRating/review stars!).
 * - Generates dynamic XML sitemaps respecting `noindex` and published status.
 * - Audits missing/duplicate titles, descriptions, and missing image alt text.
 */
final class SeoService
{
    /**
     * @return array<string, mixed>
     */
    public function organizationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'TravelAgency',
            'name' => config('precious.fallback_brand.name', 'Precious Pakistan'),
            'url' => rtrim((string) config('app.url'), '/'),
            'email' => config('precious.fallback_brand.support_email', 'bookings@preciouspakistan.com'),
            'areaServed' => 'PK',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function destinationSchema(Destination $destination): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'TouristDestination',
            'name' => $destination->name,
            'description' => $destination->seo_description ?: $destination->summary,
            'url' => route('destinations.show', ['slug' => $destination->slug]),
        ];

        if ($destination->latitude !== null && $destination->longitude !== null) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $destination->latitude,
                'longitude' => (float) $destination->longitude,
            ];
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    public function tourSchema(Tour $tour): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'TouristTrip',
            'name' => $tour->title,
            'description' => $tour->seo_description ?: $tour->summary,
            'url' => route('tours.show', ['slug' => $tour->slug]),
            'provider' => [
                '@type' => 'TravelAgency',
                'name' => 'Precious Pakistan',
            ],
        ];

        if ($tour->pricing_model !== Tour::PRICING_CUSTOM_QUOTE && $tour->base_price_pkr !== null && bccomp((string) $tour->base_price_pkr, '0.00', 2) > 0) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => 'PKR',
                'price' => number_format((float) $tour->base_price_pkr, 2, '.', ''),
                'availability' => 'https://schema.org/InStock',
                'url' => route('tours.show', ['slug' => $tour->slug]),
            ];
        }

        // Deliberately NEVER injects fabricated aggregateRating or review counts.
        return $schema;
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public function breadcrumbSchema(array $items): array
    {
        $list = [];
        foreach (array_values($items) as $index => $item) {
            $list[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }

    /**
     * @param  list<array{question: string, answer: string}>  $faqs
     * @return array<string, mixed>
     */
    public function faqSchema(array $faqs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $f): array => [
                '@type' => 'Question',
                'name' => $f['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($f['answer']),
                ],
            ], $faqs),
        ];
    }

    /**
     * Collect indexable canonical URLs for `/sitemap.xml`, strictly omitting drafts and `noindex` records.
     *
     * @return list<array{loc: string, lastmod: string, priority: string}>
     */
    public function sitemapEntries(): array
    {
        $entries = [];

        foreach ([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('destinations.index'), 'priority' => '0.9'],
            ['loc' => route('tours.index'), 'priority' => '0.9'],
            ['loc' => route('fleet.index'), 'priority' => '0.7'],
            ['loc' => route('nomads.index'), 'priority' => '0.7'],
        ] as $static) {
            $entries[] = [
                'loc' => $static['loc'],
                'lastmod' => now()->toAtomString(),
                'priority' => $static['priority'],
            ];
        }

        CmsPage::query()
            ->where('status', CmsPage::STATUS_PUBLISHED)
            ->where('is_homepage', false)
            ->where('noindex', false)
            ->get()
            ->each(function (CmsPage $page) use (&$entries): void {
                $entries[] = [
                    'loc' => url('/'.$page->slug),
                    'lastmod' => ($page->updated_at ?? now())->toAtomString(),
                    'priority' => '0.7',
                ];
            });

        Region::query()
            ->where('is_active', true)
            ->where('noindex', false)
            ->get()
            ->each(function (Region $region) use (&$entries): void {
                $entries[] = [
                    'loc' => route('regions.show', ['slug' => $region->slug]),
                    'lastmod' => ($region->updated_at ?? now())->toAtomString(),
                    'priority' => '0.8',
                ];
            });

        Destination::query()
            ->where('is_active', true)
            ->where('noindex', false)
            ->get()
            ->each(function (Destination $dest) use (&$entries): void {
                $entries[] = [
                    'loc' => route('destinations.show', ['slug' => $dest->slug]),
                    'lastmod' => ($dest->updated_at ?? now())->toAtomString(),
                    'priority' => '0.8',
                ];
            });

        Tour::query()
            ->where('status', Tour::STATUS_PUBLISHED)
            ->where('noindex', false)
            ->get()
            ->each(function (Tour $tour) use (&$entries): void {
                $entries[] = [
                    'loc' => route('tours.show', ['slug' => $tour->slug]),
                    'lastmod' => ($tour->updated_at ?? now())->toAtomString(),
                    'priority' => '0.9',
                ];
            });

        return $entries;
    }

    /**
     * Audit warnings across CMS pages, Tours, Destinations, and Media Library.
     *
     * @return array{missing_seo_titles: int, missing_seo_descriptions: int, duplicate_titles: list<string>, missing_media_alt: int}
     */
    public function audit(): array
    {
        $missingTitles = 0;
        $missingDescriptions = 0;
        $titleCounts = [];

        $check = function (?string $title, ?string $desc) use (&$missingTitles, &$missingDescriptions, &$titleCounts): void {
            $t = trim((string) $title);
            $d = trim((string) $desc);
            if ($t === '') {
                $missingTitles++;
            } else {
                $key = strtolower($t);
                $titleCounts[$key] = ($titleCounts[$key] ?? 0) + 1;
            }
            if ($d === '') {
                $missingDescriptions++;
            }
        };

        CmsPage::query()->where('status', CmsPage::STATUS_PUBLISHED)->get()
            ->each(fn (CmsPage $p) => $check($p->seo_title ?: $p->title, $p->seo_description));
        Tour::query()->where('status', Tour::STATUS_PUBLISHED)->get()
            ->each(fn (Tour $t) => $check($t->seo_title ?: $t->title, $t->seo_description ?: $t->summary));
        Destination::query()->where('is_active', true)->get()
            ->each(fn (Destination $d) => $check($d->seo_title ?: $d->name, $d->seo_description ?: $d->summary));

        $duplicates = array_keys(array_filter($titleCounts, fn (int $c): bool => $c > 1));
        $missingAlt = MediaAsset::query()->where(function ($q): void {
            $q->whereNull('alt_text')->orWhere('alt_text', '');
        })->count();

        return [
            'missing_seo_titles' => $missingTitles,
            'missing_seo_descriptions' => $missingDescriptions,
            'duplicate_titles' => array_values($duplicates),
            'missing_media_alt' => $missingAlt,
        ];
    }
}
