<?php

namespace Database\Seeders;

use App\Modules\Cms\Models\CmsMenu;
use App\Modules\Cms\Models\CmsPage;
use App\Modules\Cms\Services\MenuService;
use App\Modules\Cms\Services\PageBuilderService;
use App\Modules\Cms\Services\SiteSettingsService;
use Illuminate\Database\Seeder;

class CmsDefaultPagesSeeder extends Seeder
{
    public function run(
        PageBuilderService $builder,
        SiteSettingsService $settings,
        MenuService $menus,
    ): void {
        $settings->set('theme_color_preset', 'karakoram_emerald', 'appearance', 'string', true);
        $settings->set('theme_typography_preset', 'modern_editorial', 'appearance', 'string', true);
        $settings->set('brand_logo_url', '', 'appearance', 'string', true);
        $settings->set('social_links', [
            ['platform' => 'facebook', 'label' => 'Facebook', 'url' => 'https://facebook.com/preciouspakistan'],
            ['platform' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com/preciouspakistan'],
            ['platform' => 'youtube', 'label' => 'YouTube', 'url' => 'https://youtube.com/@preciouspakistan'],
            ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://linkedin.com/company/preciouspakistan'],
        ], 'social', 'json', true);

        $this->seedMenus($menus);
        $this->seedPages($builder);
    }

    private function seedMenus(MenuService $menus): void
    {
        $definitions = [
            CmsMenu::LOCATION_HEADER => [
                'name' => 'Header Primary Navigation',
                'items' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Tours', 'url' => '/tours'],
                    ['label' => 'Destinations', 'url' => '/destinations'],
                    ['label' => 'About', 'url' => '/about'],
                    ['label' => 'FAQs', 'url' => '/faqs'],
                    ['label' => 'Contact', 'url' => '/contact'],
                ],
            ],
            CmsMenu::LOCATION_MOBILE => [
                'name' => 'Mobile Drawer Navigation',
                'items' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Tours', 'url' => '/tours'],
                    ['label' => 'Destinations', 'url' => '/destinations'],
                    ['label' => 'About Us', 'url' => '/about'],
                    ['label' => 'FAQs', 'url' => '/faqs'],
                    ['label' => 'Contact & Quote', 'url' => '/contact'],
                ],
            ],
            CmsMenu::LOCATION_FOOTER_PRIMARY => [
                'name' => 'Footer Explore Links',
                'items' => [
                    ['label' => 'About Precious Pakistan', 'url' => '/about'],
                    ['label' => 'Pakistan Regions', 'url' => '/#regions'],
                    ['label' => 'Company Fleet', 'url' => '/#fleet'],
                    ['label' => 'Digital Nomads', 'url' => '/#digital-nomads'],
                    ['label' => 'FAQs', 'url' => '/faqs'],
                    ['label' => 'Contact', 'url' => '/contact'],
                ],
            ],
            CmsMenu::LOCATION_FOOTER_LEGAL => [
                'name' => 'Footer Legal & Policies',
                'items' => [
                    ['label' => 'Terms & Conditions', 'url' => '/terms'],
                    ['label' => 'Privacy Policy', 'url' => '/privacy'],
                ],
            ],
        ];

        foreach ($definitions as $location => $def) {
            $menu = CmsMenu::query()->firstOrCreate(
                ['location' => $location],
                ['name' => $def['name'], 'is_active' => true]
            );

            if ($menu->items()->count() === 0) {
                foreach ($def['items'] as $idx => $item) {
                    $menu->items()->create([
                        'label' => $item['label'],
                        'url' => $item['url'],
                        'target' => '_self',
                        'sort_order' => $idx,
                        'is_visible' => true,
                        'device_visibility' => 'all',
                    ]);
                }
            }
        }

        $menus->flushAll();
    }

    private function seedPages(PageBuilderService $builder): void
    {
        if (! CmsPage::query()->where('is_homepage', true)->exists()) {
            $builder->createPage(null, [
                'title' => 'Precious Pakistan — Home',
                'slug' => 'home',
                'is_homepage' => true,
                'seo_title' => 'Precious Pakistan — Company-Operated Pakistan Tourism Platform',
                'seo_description' => 'Explore Gilgit-Baltistan, AJK, KPK, Punjab, Sindh, Balochistan, and Islamabad with company-operated tours, driver-included vehicles, and verified stays.',
            ], [
                [
                    'component_type' => 'hero_banner',
                    'style_preset' => 'dark',
                    'config' => [
                        'badge' => 'Company-Operated Pakistan Tourism Platform',
                        'heading' => 'Explore the Karakoram, Valleys, Heritage & Coast of Pakistan',
                        'subheading' => 'Custom itineraries across all seven regions of Pakistan with driver-included company fleet, verified tour-only accommodation options, optional Digital Nomad workspaces, and immutable PKR/USD quotations.',
                        'primary_cta_label' => 'Request a Custom Quote',
                        'primary_cta_href' => '/contact',
                        'secondary_cta_label' => 'Explore Regions',
                        'secondary_cta_href' => '#regions',
                        'stats' => [
                            ['label' => 'Coverage', 'value' => 'All 7 Regions'],
                            ['label' => 'Company Fleet', 'value' => 'Driver Included'],
                            ['label' => 'Billing', 'value' => 'PKR & USD Invoices'],
                        ],
                    ],
                ],
                [
                    'component_type' => 'destination_grid',
                    'style_preset' => 'default',
                    'config' => [
                        'eyebrow' => 'Nationwide Coverage',
                        'heading' => 'All Seven Regions of Pakistan',
                        'subtitle' => 'Authentic, company-managed routes spanning Pakistan’s mountains, historic capitals, fertile valleys, and coastline.',
                        'regions' => [
                            ['name' => 'Gilgit-Baltistan', 'highlights' => 'Hunza, Skardu, Gilgit, Ghizer, Nagar, Astore, Shigar, Ghanche, Kharmang', 'tag' => 'High Peaks'],
                            ['name' => 'Azad Jammu & Kashmir', 'highlights' => 'Neelum Valley, Muzaffarabad', 'tag' => 'Alpine Valleys'],
                            ['name' => 'Khyber Pakhtunkhwa', 'highlights' => 'Swat, Naran, Chitral', 'tag' => 'Mountain Heritage'],
                            ['name' => 'Punjab', 'highlights' => 'Lahore, Murree', 'tag' => 'Culture & Hills'],
                            ['name' => 'Sindh', 'highlights' => 'Karachi, Mohenjo-daro', 'tag' => 'Coast & Indus'],
                            ['name' => 'Balochistan', 'highlights' => 'Quetta, Ziarat, Gwadar', 'tag' => 'Highlands & Sea'],
                            ['name' => 'Islamabad Capital Territory', 'highlights' => 'Islamabad & Margalla Foothills', 'tag' => 'Gateway Capital'],
                        ],
                    ],
                ],
                [
                    'component_type' => 'tours_carousel',
                    'style_preset' => 'surface',
                    'config' => [
                        'eyebrow' => 'Sample Preview Catalog',
                        'heading' => 'Signature Pakistan Expeditions',
                        'subtitle' => 'Sample itineraries illustrating Precious Pakistan’s quote-first, transparent pricing workflow.',
                        'items' => [
                            [
                                'title' => 'Hunza & Attabad Scenic Expedition',
                                'region' => 'Gilgit-Baltistan',
                                'duration' => '7 Days / 6 Nights',
                                'price_display' => 'Request Quote',
                                'summary' => 'Sample itinerary covering Islamabad gateway transfer, Karimabad heritage forts, Passu Cones, and driver-included SUV.',
                                'cta_href' => '/contact',
                                'is_sample' => true,
                            ],
                            [
                                'title' => 'Swat Valley & Kalam Mountain Retreat',
                                'region' => 'Khyber Pakhtunkhwa',
                                'duration' => '5 Days / 4 Nights',
                                'price_display' => 'Request Quote',
                                'summary' => 'Sample alpine retreat combining river valleys, Malam Jabba views, curated tour-only stays, and flexible fuel plans.',
                                'cta_href' => '/contact',
                                'is_sample' => true,
                            ],
                            [
                                'title' => 'Lahore, Islamabad & Indus Heritage Circuit',
                                'region' => 'Punjab & ICT',
                                'duration' => '4 Days / 3 Nights',
                                'price_display' => 'Request Quote',
                                'summary' => 'Sample cultural journey across Mughal architecture, Margalla viewpoints, and private company sedan transport.',
                                'cta_href' => '/contact',
                                'is_sample' => true,
                            ],
                        ],
                    ],
                ],
                [
                    'component_type' => 'vehicles_carousel',
                    'style_preset' => 'default',
                    'config' => [
                        'eyebrow' => 'Company-Owned & Managed Fleet',
                        'heading' => 'Reliable Vehicles With Included Drivers',
                        'subtitle' => 'Select seasonal daily rates with fuel or without fuel — zero duplicate driver charges.',
                        'driver_note' => 'Professional company driver is ALWAYS included in every vehicle rental rate.',
                        'items' => [
                            ['name' => 'Toyota Prado / Land Cruiser 4x4', 'category' => 'Mountain SUV', 'seats' => '4–6 Guests', 'fuel_note' => 'With-fuel or without-fuel seasonal rate', 'summary' => 'Sample fleet option suited for Hunza, Skardu, Deosai, and Chitral mountain routes.', 'is_sample' => true],
                            ['name' => 'Toyota HiAce / Grand Cabin', 'category' => 'Group Van', 'seats' => '10–14 Guests', 'fuel_note' => 'With-fuel or without-fuel seasonal rate', 'summary' => 'Sample family & corporate tour van with generous luggage capacity.', 'is_sample' => true],
                            ['name' => 'Toyota Corolla / Civic Sedan', 'category' => 'City & Highway', 'seats' => '3–4 Guests', 'fuel_note' => 'With-fuel or without-fuel seasonal rate', 'summary' => 'Sample executive sedan for Islamabad, Lahore, Murree, and Swat paved highways.', 'is_sample' => true],
                        ],
                    ],
                ],
                [
                    'component_type' => 'digital_nomad_section',
                    'style_preset' => 'dark',
                    'config' => [
                        'badge' => 'Tour Add-On Only',
                        'heading' => 'Work Remotely From Pakistan’s Mountain Valleys',
                        'body' => 'Pair your guided Pakistan itinerary with reserved coworking desks and private meeting rooms at selected northern destinations.',
                        'highlights' => [
                            'Add workspace days directly inside your tour quotation',
                            'Backup power (solar/UPS/generator) details disclosed upfront',
                            'Transparent internet provider & realistic speed disclosures',
                            'Dedicated booking officer verification prior to confirmation',
                        ],
                        'connectivity_disclaimer' => 'High-altitude connectivity and grid power can fluctuate during mountain weather events; exact backup and ISP details are shown for each workspace before confirmation.',
                        'cta_label' => 'Inquire About Nomad Add-Ons',
                        'cta_href' => '/contact',
                    ],
                ],
                [
                    'component_type' => 'cta_banner',
                    'style_preset' => 'dark',
                    'config' => [
                        'badge' => 'Direct Company Operations',
                        'heading' => 'Ready to Plan Your Journey Across Pakistan?',
                        'body' => 'Speak directly with a Precious Pakistan booking officer for a detailed PKR or USD quotation.',
                        'primary_cta_label' => 'Contact Our Team',
                        'primary_cta_href' => '/contact',
                        'secondary_cta_label' => 'Read FAQs',
                        'secondary_cta_href' => '/faqs',
                    ],
                ],
                [
                    'component_type' => 'seo_block',
                    'style_preset' => 'surface',
                    'config' => [
                        'eyebrow' => 'About Precious Pakistan 2.0',
                        'heading' => 'Transparent, Company-Operated Tourism Across Pakistan',
                        'body_html' => '<p>Precious Pakistan designs and operates private, family, trekking, cultural, and remote-work journeys throughout Gilgit-Baltistan, Azad Jammu &amp; Kashmir, Khyber Pakhtunkhwa, Punjab, Sindh, Balochistan, and Islamabad Capital Territory.</p><p>Every quotation separates tour package services, company vehicle seasonal pricing (with driver included), verified overnight hotel preferences, and optional workspace add-ons into an auditable PKR or USD invoice.</p>',
                    ],
                ],
            ], true);
        }

        $staticPages = [
            [
                'slug' => 'about',
                'title' => 'About Precious Pakistan',
                'seo_title' => 'About Precious Pakistan — Company-Operated Tourism',
                'seo_description' => 'Learn how Precious Pakistan operates curated tours, company-managed transport with included drivers, and manually verified accommodation options.',
                'sections' => [
                    [
                        'component_type' => 'hero_banner',
                        'style_preset' => 'dark',
                        'config' => [
                            'badge' => 'Who We Are',
                            'heading' => 'Company-Operated Travel Built on Clarity & Local Care',
                            'subheading' => 'We are a dedicated Pakistan tour operator — never an unverified multi-vendor marketplace.',
                            'primary_cta_label' => 'Contact Booking Officers',
                            'primary_cta_href' => '/contact',
                        ],
                    ],
                    [
                        'component_type' => 'content_block',
                        'style_preset' => 'surface',
                        'config' => [
                            'eyebrow' => 'Our Operating Model',
                            'heading' => 'End-to-End Accountability on Every Journey',
                            'body_html' => '<p>Precious Pakistan plans and operates itineraries directly through our trained internal team of tour managers, booking officers, fleet managers, and local guides.</p><ul><li><strong>Company Fleet:</strong> Drivers are always included in vehicle pricing with clear seasonal with-fuel or without-fuel options.</li><li><strong>Tour-Only Hotels:</strong> Customers select preferred overnight hotels inside tours, and our booking officers externally verify real availability before final confirmation.</li><li><strong>Immutable PKR &amp; USD Invoices:</strong> Accepted quotes lock in itemized breakdowns and exchange-rate snapshots for complete clarity.</li></ul>',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact Precious Pakistan',
                'seo_title' => 'Contact Precious Pakistan — Tour Inquiries & Quotations',
                'seo_description' => 'Reach Precious Pakistan booking officers for custom itineraries, fleet quotations, and Digital Nomad tour add-ons.',
                'sections' => [
                    [
                        'component_type' => 'contact_form',
                        'style_preset' => 'default',
                        'config' => [
                            'eyebrow' => 'Get In Touch',
                            'heading' => 'Request a Custom Pakistan Tour Quotation',
                            'subtitle' => 'Share your preferred regions, travel dates, group size, and fuel/accommodation preferences.',
                            'show_phone_field' => true,
                            'show_trip_dates_field' => true,
                            'consent_text' => 'I consent to Precious Pakistan using my inquiry details to prepare a travel quotation.',
                        ],
                    ],
                    [
                        'component_type' => 'map_embed',
                        'style_preset' => 'surface',
                        'config' => [
                            'eyebrow' => 'Gateway Hub',
                            'heading' => 'Islamabad Operations Gateway',
                            'subtitle' => 'Most northern mountain expeditions depart via Islamabad International Airport (ISB).',
                            'center_lat' => 33.6844,
                            'center_lng' => 73.0479,
                            'zoom' => 7,
                            'markers' => [
                                ['title' => 'Islamabad Gateway', 'region' => 'Islamabad Capital Territory', 'lat' => 33.6844, 'lng' => 73.0479, 'description' => 'Primary departure hub for northern & nationwide tours.'],
                                ['title' => 'Hunza Valley', 'region' => 'Gilgit-Baltistan', 'lat' => 36.3167, 'lng' => 74.6500, 'description' => 'Karimabad & Karakoram Highway scenic corridor.'],
                                ['title' => 'Skardu', 'region' => 'Gilgit-Baltistan', 'lat' => 35.2971, 'lng' => 75.6333, 'description' => 'Gateway to Shigar, Deosai, and Baltoro expeditions.'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms & Conditions',
                'seo_title' => 'Terms & Conditions — Precious Pakistan',
                'seo_description' => 'Booking request, external hotel verification, seasonal fleet pricing, and PKR/USD invoice terms for Precious Pakistan.',
                'sections' => [
                    [
                        'component_type' => 'content_block',
                        'style_preset' => 'surface',
                        'config' => [
                            'eyebrow' => 'Policies',
                            'heading' => 'Terms & Conditions',
                            'body_html' => '<p>All online bookings submitted on Precious Pakistan begin as booking requests until our booking officer verifies accommodation availability externally and validates fleet assignment.</p><h3>Pricing &amp; Invoices</h3><p>Accepted quotations and issued PKR or USD invoices preserve an immutable price snapshot, including exchange rate metadata where applicable. Post-issue adjustments are handled through formal credit notes or revised invoices.</p><h3>Company Fleet</h3><p>All company vehicles include a professional driver in the quoted daily rate. Trips crossing seasonal or annual pricing periods are prorated by chargeable date.</p>',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'privacy',
                'title' => 'Privacy Policy',
                'seo_title' => 'Privacy Policy — Precious Pakistan',
                'seo_description' => 'How Precious Pakistan collects, safeguards, and retains traveler inquiry and booking data.',
                'sections' => [
                    [
                        'component_type' => 'content_block',
                        'style_preset' => 'surface',
                        'config' => [
                            'eyebrow' => 'Data Protection',
                            'heading' => 'Privacy Policy',
                            'body_html' => '<p>Precious Pakistan collects only the traveler contact details, trip preferences, and billing metadata necessary to quote, verify, and operate your tour safely.</p><p>We never expose sensitive personal information in public links or QR verification tokens, and access to customer records inside our admin portal is strictly scoped by server-side RBAC permissions with immutable audit logging.</p>',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'faqs',
                'title' => 'Frequently Asked Questions',
                'seo_title' => 'FAQs — Precious Pakistan Tours, Fleet & Digital Nomads',
                'seo_description' => 'Answers to common questions about driver-included vehicles, tour-only hotels, PKR/USD invoices, and Digital Nomad add-ons.',
                'sections' => [
                    [
                        'component_type' => 'accordion_faq',
                        'style_preset' => 'default',
                        'config' => [
                            'eyebrow' => 'Help Center',
                            'heading' => 'Frequently Asked Questions',
                            'subtitle' => 'Clear answers about how Precious Pakistan quotes and operates your journey.',
                            'items' => [
                                [
                                    'question' => 'Is a driver included in company vehicle rental rates?',
                                    'answer_html' => '<p><strong>Yes.</strong> Every company vehicle rate always includes a professional driver at zero extra charge. You only choose between our transparent <em>with-fuel</em> and <em>without-fuel</em> seasonal daily rates.</p>',
                                ],
                                [
                                    'question' => 'Can I book a standalone hotel without a tour?',
                                    'answer_html' => '<p>No. Hotels listed on Precious Pakistan are offered exclusively as accommodation options within our curated tours. Once you select your preferred hotel and room type, a company booking officer externally verifies availability before final confirmation.</p>',
                                ],
                                [
                                    'question' => 'Which currencies are supported for quotations and invoices?',
                                    'answer_html' => '<p>We issue auditable quotations and invoices in <strong>PKR</strong> or <strong>USD</strong>, with locked line-item snapshots and recorded exchange-rate metadata.</p>',
                                ],
                                [
                                    'question' => 'How do Digital Nomad workspace add-ons work?',
                                    'answer_html' => '<p>You can attach coworking desk days or meeting rooms at supported northern destinations directly inside your tour booking. Each listing discloses honest internet and backup power details before confirmation.</p>',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($staticPages as $pageDef) {
            if (CmsPage::query()->where('slug', $pageDef['slug'])->exists()) {
                continue;
            }

            $builder->createPage(null, [
                'title' => $pageDef['title'],
                'slug' => $pageDef['slug'],
                'is_homepage' => false,
                'seo_title' => $pageDef['seo_title'],
                'seo_description' => $pageDef['seo_description'],
            ], $pageDef['sections'], true);
        }
    }
}
