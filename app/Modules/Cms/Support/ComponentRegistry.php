<?php

namespace App\Modules\Cms\Support;

use InvalidArgumentException;

final class ComponentRegistry
{
    /**
     * @var array<string, string>
     */
    public const COMPONENT_TYPES = [
        'hero_banner' => 'Hero Banner',
        'content_block' => 'Content Block (Rich Text)',
        'cards_grid' => 'Cards Grid',
        'image_gallery' => 'Image Gallery',
        'cta_banner' => 'Call-to-Action Banner',
        'testimonials' => 'Testimonials',
        'accordion_faq' => 'Accordion / FAQs',
        'contact_form' => 'Contact / Inquiry Form',
        'map_embed' => 'Interactive Map Block',
        'tours_carousel' => 'Tours Showcase Carousel',
        'destination_grid' => 'Pakistan Destinations Grid',
        'vehicles_carousel' => 'Company Fleet Carousel',
        'digital_nomad_section' => 'Digital Nomad Section',
        'social_icons' => 'Social Links Block',
        'seo_block' => 'SEO Editorial Block',
        'announcement_strip' => 'Announcement Strip',
    ];

    public function __construct(
        private readonly HtmlSanitizerService $sanitizer,
    ) {}

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return self::COMPONENT_TYPES;
    }

    public static function exists(string $type): bool
    {
        return array_key_exists($type, self::COMPONENT_TYPES);
    }

    /**
     * Validate component type and sanitize every accepted configuration field.
     * Unknown keys are stripped; executable PHP/Blade/JS/unsafe URLs are rejected.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validateAndSanitize(string $type, array $config): array
    {
        if (! self::exists($type)) {
            throw new InvalidArgumentException("Unsupported CMS component type [{$type}].");
        }

        return match ($type) {
            'hero_banner' => [
                'badge' => $this->sanitizer->sanitizePlainText($config['badge'] ?? null, 'badge', 120),
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 220),
                'subheading' => $this->sanitizer->sanitizePlainText($config['subheading'] ?? null, 'subheading', 600),
                'primary_cta_label' => $this->sanitizer->sanitizePlainText($config['primary_cta_label'] ?? null, 'primary_cta_label', 80),
                'primary_cta_href' => $this->sanitizer->sanitizeUrl($config['primary_cta_href'] ?? null, 'primary_cta_href'),
                'secondary_cta_label' => $this->sanitizer->sanitizePlainText($config['secondary_cta_label'] ?? null, 'secondary_cta_label', 80),
                'secondary_cta_href' => $this->sanitizer->sanitizeUrl($config['secondary_cta_href'] ?? null, 'secondary_cta_href'),
                'stats' => $this->sanitizeKeyValueItems($config['stats'] ?? [], 'label', 'value', 4),
            ],

            'content_block' => [
                'eyebrow' => $this->sanitizer->sanitizePlainText($config['eyebrow'] ?? null, 'eyebrow', 80),
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 180),
                'body_html' => $this->sanitizer->sanitizeRichHtml((string) ($config['body_html'] ?? ''), 'body_html'),
                'alignment' => in_array($config['alignment'] ?? 'left', ['left', 'center'], true) ? ($config['alignment'] ?? 'left') : 'left',
            ],

            'cards_grid' => [
                'eyebrow' => $this->sanitizer->sanitizePlainText($config['eyebrow'] ?? null, 'eyebrow', 80),
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 180),
                'subtitle' => $this->sanitizer->sanitizePlainText($config['subtitle'] ?? null, 'subtitle', 400),
                'columns' => in_array((int) ($config['columns'] ?? 3), [2, 3, 4], true) ? (int) $config['columns'] : 3,
                'cards' => $this->sanitizeCards($config['cards'] ?? []),
            ],

            'image_gallery' => [
                'eyebrow' => $this->sanitizer->sanitizePlainText($config['eyebrow'] ?? null, 'eyebrow', 80),
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 180),
                'subtitle' => $this->sanitizer->sanitizePlainText($config['subtitle'] ?? null, 'subtitle', 400),
                'images' => $this->sanitizeGalleryImages($config['images'] ?? []),
            ],

            'cta_banner' => [
                'badge' => $this->sanitizer->sanitizePlainText($config['badge'] ?? null, 'badge', 80),
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 180),
                'body' => $this->sanitizer->sanitizePlainText($config['body'] ?? null, 'body', 500),
                'primary_cta_label' => $this->sanitizer->sanitizePlainText($config['primary_cta_label'] ?? null, 'primary_cta_label', 80),
                'primary_cta_href' => $this->sanitizer->sanitizeUrl($config['primary_cta_href'] ?? null, 'primary_cta_href'),
                'secondary_cta_label' => $this->sanitizer->sanitizePlainText($config['secondary_cta_label'] ?? null, 'secondary_cta_label', 80),
                'secondary_cta_href' => $this->sanitizer->sanitizeUrl($config['secondary_cta_href'] ?? null, 'secondary_cta_href'),
            ],

            'testimonials' => [
                'eyebrow' => $this->sanitizer->sanitizePlainText($config['eyebrow'] ?? null, 'eyebrow', 80),
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 180),
                'items' => $this->sanitizeTestimonials($config['items'] ?? []),
            ],

            'accordion_faq' => [
                'eyebrow' => $this->sanitizer->sanitizePlainText($config['eyebrow'] ?? null, 'eyebrow', 80),
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 180),
                'subtitle' => $this->sanitizer->sanitizePlainText($config['subtitle'] ?? null, 'subtitle', 400),
                'items' => $this->sanitizeFaqItems($config['items'] ?? []),
            ],

            'contact_form' => [
                'eyebrow' => $this->sanitizer->sanitizePlainText($config['eyebrow'] ?? null, 'eyebrow', 80),
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 180),
                'subtitle' => $this->sanitizer->sanitizePlainText($config['subtitle'] ?? null, 'subtitle', 400),
                'show_phone_field' => (bool) ($config['show_phone_field'] ?? true),
                'show_trip_dates_field' => (bool) ($config['show_trip_dates_field'] ?? true),
                'consent_text' => $this->sanitizer->sanitizePlainText(
                    $config['consent_text'] ?? 'I consent to Precious Pakistan contacting me regarding my travel inquiry.',
                    'consent_text',
                    300
                ),
            ],

            'map_embed' => [
                'eyebrow' => $this->sanitizer->sanitizePlainText($config['eyebrow'] ?? null, 'eyebrow', 80),
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 180),
                'subtitle' => $this->sanitizer->sanitizePlainText($config['subtitle'] ?? null, 'subtitle', 400),
                'center_lat' => max(-90.0, min(90.0, (float) ($config['center_lat'] ?? 33.6844))),
                'center_lng' => max(-180.0, min(180.0, (float) ($config['center_lng'] ?? 73.0479))),
                'zoom' => max(4, min(16, (int) ($config['zoom'] ?? 6))),
                'markers' => $this->sanitizeMarkers($config['markers'] ?? []),
            ],

            'tours_carousel' => [
                'eyebrow' => $this->sanitizer->sanitizePlainText($config['eyebrow'] ?? null, 'eyebrow', 80),
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 180),
                'subtitle' => $this->sanitizer->sanitizePlainText($config['subtitle'] ?? null, 'subtitle', 400),
                'items' => $this->sanitizeTourItems($config['items'] ?? []),
            ],

            'destination_grid' => [
                'eyebrow' => $this->sanitizer->sanitizePlainText($config['eyebrow'] ?? null, 'eyebrow', 80),
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 180),
                'subtitle' => $this->sanitizer->sanitizePlainText($config['subtitle'] ?? null, 'subtitle', 400),
                'regions' => $this->sanitizeRegions($config['regions'] ?? []),
            ],

            'vehicles_carousel' => [
                'eyebrow' => $this->sanitizer->sanitizePlainText($config['eyebrow'] ?? null, 'eyebrow', 80),
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 180),
                'subtitle' => $this->sanitizer->sanitizePlainText($config['subtitle'] ?? null, 'subtitle', 400),
                'driver_note' => $this->sanitizer->sanitizePlainText(
                    $config['driver_note'] ?? 'Professional company driver is always included in every vehicle rate.',
                    'driver_note',
                    250
                ),
                'items' => $this->sanitizeVehicleItems($config['items'] ?? []),
            ],

            'digital_nomad_section' => [
                'badge' => $this->sanitizer->sanitizePlainText($config['badge'] ?? null, 'badge', 80),
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 180),
                'body' => $this->sanitizer->sanitizePlainText($config['body'] ?? null, 'body', 700),
                'highlights' => $this->sanitizeStringList($config['highlights'] ?? [], 8, 160),
                'connectivity_disclaimer' => $this->sanitizer->sanitizePlainText(
                    $config['connectivity_disclaimer'] ?? 'Internet speeds and backup power vary by mountain location and are disclosed transparently per workspace.',
                    'connectivity_disclaimer',
                    350
                ),
                'cta_label' => $this->sanitizer->sanitizePlainText($config['cta_label'] ?? null, 'cta_label', 80),
                'cta_href' => $this->sanitizer->sanitizeUrl($config['cta_href'] ?? null, 'cta_href'),
            ],

            'social_icons' => [
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 140),
                'subtitle' => $this->sanitizer->sanitizePlainText($config['subtitle'] ?? null, 'subtitle', 300),
                'links' => $this->sanitizeSocialLinks($config['links'] ?? []),
            ],

            'seo_block' => [
                'eyebrow' => $this->sanitizer->sanitizePlainText($config['eyebrow'] ?? null, 'eyebrow', 80),
                'heading' => $this->sanitizer->sanitizePlainText($config['heading'] ?? null, 'heading', 180),
                'body_html' => $this->sanitizer->sanitizeRichHtml((string) ($config['body_html'] ?? ''), 'body_html'),
            ],

            'announcement_strip' => [
                'text' => $this->sanitizer->sanitizePlainText($config['text'] ?? null, 'text', 250),
                'link_label' => $this->sanitizer->sanitizePlainText($config['link_label'] ?? null, 'link_label', 60),
                'link_href' => $this->sanitizer->sanitizeUrl($config['link_href'] ?? null, 'link_href'),
            ],
        };
    }

    /**
     * @return list<array<string, string>>
     */
    private function sanitizeKeyValueItems(mixed $items, string $k1, string $k2, int $limit): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($items), 0, $limit) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $v1 = $this->sanitizer->sanitizePlainText($item[$k1] ?? null, $k1, 60);
            $v2 = $this->sanitizer->sanitizePlainText($item[$k2] ?? null, $k2, 80);
            if ($v1 !== '' || $v2 !== '') {
                $out[] = [$k1 => $v1, $k2 => $v2];
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sanitizeCards(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($items), 0, 12) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $out[] = [
                'badge' => $this->sanitizer->sanitizePlainText($item['badge'] ?? null, 'card.badge', 60),
                'title' => $this->sanitizer->sanitizePlainText($item['title'] ?? null, 'card.title', 140),
                'body' => $this->sanitizer->sanitizePlainText($item['body'] ?? null, 'card.body', 400),
                'cta_label' => $this->sanitizer->sanitizePlainText($item['cta_label'] ?? null, 'card.cta_label', 60),
                'cta_href' => $this->sanitizer->sanitizeUrl($item['cta_href'] ?? null, 'card.cta_href'),
                'is_sample' => (bool) ($item['is_sample'] ?? false),
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sanitizeGalleryImages(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($items), 0, 16) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $out[] = [
                'url' => $this->sanitizer->sanitizeUrl($item['url'] ?? null, 'gallery.url'),
                'alt' => $this->sanitizer->sanitizePlainText($item['alt'] ?? null, 'gallery.alt', 180),
                'caption' => $this->sanitizer->sanitizePlainText($item['caption'] ?? null, 'gallery.caption', 220),
                'is_sample' => (bool) ($item['is_sample'] ?? true),
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sanitizeTestimonials(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($items), 0, 9) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $out[] = [
                'quote' => $this->sanitizer->sanitizePlainText($item['quote'] ?? null, 'testimonial.quote', 500),
                'author_name' => $this->sanitizer->sanitizePlainText($item['author_name'] ?? null, 'testimonial.author_name', 100),
                'author_origin' => $this->sanitizer->sanitizePlainText($item['author_origin'] ?? null, 'testimonial.author_origin', 100),
                'trip_context' => $this->sanitizer->sanitizePlainText($item['trip_context'] ?? null, 'testimonial.trip_context', 120),
                'is_sample' => (bool) ($item['is_sample'] ?? true),
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string, string>>
     */
    private function sanitizeFaqItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($items), 0, 25) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $question = $this->sanitizer->sanitizePlainText($item['question'] ?? null, 'faq.question', 240);
            $answer = $this->sanitizer->sanitizeRichHtml((string) ($item['answer_html'] ?? ''), 'faq.answer_html');
            if ($question !== '') {
                $out[] = [
                    'question' => $question,
                    'answer_html' => $answer,
                ];
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sanitizeMarkers(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($items), 0, 20) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $out[] = [
                'title' => $this->sanitizer->sanitizePlainText($item['title'] ?? null, 'marker.title', 120),
                'region' => $this->sanitizer->sanitizePlainText($item['region'] ?? null, 'marker.region', 100),
                'lat' => max(-90.0, min(90.0, (float) ($item['lat'] ?? 33.6844))),
                'lng' => max(-180.0, min(180.0, (float) ($item['lng'] ?? 73.0479))),
                'description' => $this->sanitizer->sanitizePlainText($item['description'] ?? null, 'marker.description', 220),
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sanitizeTourItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($items), 0, 12) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $price = $this->sanitizer->sanitizePlainText($item['price_display'] ?? null, 'tour.price_display', 60);
            if ($price === '' || $price === '$0' || $price === 'PKR 0') {
                $price = 'Request Quote';
            }

            $out[] = [
                'title' => $this->sanitizer->sanitizePlainText($item['title'] ?? null, 'tour.title', 160),
                'region' => $this->sanitizer->sanitizePlainText($item['region'] ?? null, 'tour.region', 100),
                'duration' => $this->sanitizer->sanitizePlainText($item['duration'] ?? null, 'tour.duration', 60),
                'price_display' => $price,
                'summary' => $this->sanitizer->sanitizePlainText($item['summary'] ?? null, 'tour.summary', 400),
                'cta_href' => $this->sanitizer->sanitizeUrl($item['cta_href'] ?? '/contact', 'tour.cta_href'),
                'is_sample' => (bool) ($item['is_sample'] ?? true),
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string, string>>
     */
    private function sanitizeRegions(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($items), 0, 12) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $out[] = [
                'name' => $this->sanitizer->sanitizePlainText($item['name'] ?? null, 'region.name', 120),
                'highlights' => $this->sanitizer->sanitizePlainText($item['highlights'] ?? null, 'region.highlights', 220),
                'tag' => $this->sanitizer->sanitizePlainText($item['tag'] ?? null, 'region.tag', 60),
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sanitizeVehicleItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($items), 0, 12) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $out[] = [
                'name' => $this->sanitizer->sanitizePlainText($item['name'] ?? null, 'vehicle.name', 120),
                'category' => $this->sanitizer->sanitizePlainText($item['category'] ?? null, 'vehicle.category', 80),
                'seats' => $this->sanitizer->sanitizePlainText($item['seats'] ?? null, 'vehicle.seats', 40),
                'fuel_note' => $this->sanitizer->sanitizePlainText(
                    $item['fuel_note'] ?? 'With-fuel or without-fuel seasonal daily rates',
                    'vehicle.fuel_note',
                    140
                ),
                'summary' => $this->sanitizer->sanitizePlainText($item['summary'] ?? null, 'vehicle.summary', 300),
                'is_sample' => (bool) ($item['is_sample'] ?? true),
            ];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function sanitizeStringList(mixed $items, int $limit, int $maxLength): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($items), 0, $limit) as $item) {
            $val = $this->sanitizer->sanitizePlainText(is_string($item) ? $item : '', 'list_item', $maxLength);
            if ($val !== '') {
                $out[] = $val;
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, string>>
     */
    private function sanitizeSocialLinks(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $allowedPlatforms = ['facebook', 'instagram', 'youtube', 'linkedin', 'whatsapp'];
        $out = [];

        foreach (array_slice(array_values($items), 0, 10) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $platform = strtolower($this->sanitizer->sanitizePlainText($item['platform'] ?? null, 'social.platform', 32));
            if (! in_array($platform, $allowedPlatforms, true)) {
                continue;
            }
            $url = $this->sanitizer->sanitizeUrl($item['url'] ?? null, 'social.url');
            if ($url === '') {
                continue;
            }
            $out[] = [
                'platform' => $platform,
                'label' => $this->sanitizer->sanitizePlainText($item['label'] ?? ucfirst($platform), 'social.label', 60),
                'url' => $url,
            ];
        }

        return $out;
    }
}
