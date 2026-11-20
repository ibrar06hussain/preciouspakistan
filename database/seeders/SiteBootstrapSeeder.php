<?php

namespace Database\Seeders;

use App\Modules\Cms\Services\SiteSettingsService;
use Illuminate\Database\Seeder;

class SiteBootstrapSeeder extends Seeder
{
    public function run(SiteSettingsService $settings): void
    {
        $defaults = [
            ['general', 'site_name', 'Precious Pakistan', 'string'],
            ['general', 'site_tagline', 'Curated Journeys Across Every Province & Peak of Pakistan', 'string'],
            ['general', 'support_email', 'bookings@preciouspakistan.com', 'string'],
            ['general', 'operating_hours', 'Mon–Sat, 09:00–19:00 PKT (Asia/Karachi)', 'string'],
            ['general', 'announcement_enabled', true, 'boolean'],
            ['general', 'announcement_text', 'Company-operated Pakistan expeditions with included drivers, curated tour stays & optional Digital Nomad workspaces.', 'string'],
            ['navigation', 'primary_nav', [
                ['label' => 'Destinations', 'href' => '#regions'],
                ['label' => 'Sample Tours', 'href' => '#sample-journeys'],
                ['label' => 'Fleet & Stays', 'href' => '#company-standards'],
                ['label' => 'Digital Nomads', 'href' => '#nomad-preview'],
            ], 'json'],
            ['hero', 'hero_badge', 'Company-Operated Pakistan Tourism Platform', 'string'],
            ['hero', 'hero_title', 'Explore the Karakoram, Valleys, Heritage & Coast of Pakistan', 'string'],
            ['hero', 'hero_subtitle', 'From Gilgit-Baltistan and Azad Jammu & Kashmir to Lahore, Swat, Balochistan, Sindh, and Islamabad — plan custom journeys with company-managed vehicles, driver-included pricing, and transparent PKR/USD quotations.', 'string'],
            ['hero', 'hero_primary_cta_label', 'Create Traveler Account', 'string'],
            ['hero', 'hero_primary_cta_href', '/register', 'string'],
            ['hero', 'hero_secondary_cta_label', 'Explore Regions', 'string'],
            ['hero', 'hero_secondary_cta_href', '#regions', 'string'],
            ['showcase', 'regions_preview', [
                ['name' => 'Gilgit-Baltistan', 'highlights' => 'Hunza, Skardu, Gilgit, Ghizer, Nagar, Astore', 'tag' => 'High Peaks'],
                ['name' => 'Azad Jammu & Kashmir', 'highlights' => 'Neelum Valley, Muzaffarabad', 'tag' => 'Alpine Valleys'],
                ['name' => 'Khyber Pakhtunkhwa', 'highlights' => 'Swat, Naran, Chitral', 'tag' => 'Mountain Heritage'],
                ['name' => 'Punjab', 'highlights' => 'Lahore, Murree', 'tag' => 'Culture & Hills'],
                ['name' => 'Sindh', 'highlights' => 'Karachi, Mohenjo-daro', 'tag' => 'Coast & Indus'],
                ['name' => 'Balochistan', 'highlights' => 'Quetta, Ziarat, Gwadar', 'tag' => 'Highlands & Sea'],
                ['name' => 'Islamabad Capital Territory', 'highlights' => 'Islamabad & Margalla Foothills', 'tag' => 'Gateway Capital'],
            ], 'json'],
            ['showcase', 'sample_journeys', [
                [
                    'title' => 'Hunza & Attabad Scenic Expedition',
                    'region' => 'Gilgit-Baltistan',
                    'duration' => '7 Days / 6 Nights',
                    'price_display' => 'Request Quote',
                    'summary' => 'Sample itinerary preview covering Islamabad gateway transfer, Karimabad heritage forts, Passu Cones, and driver-included company SUV.',
                    'is_sample' => true,
                ],
                [
                    'title' => 'Swat Valley & Kalam Mountain Retreat',
                    'region' => 'Khyber Pakhtunkhwa',
                    'duration' => '5 Days / 4 Nights',
                    'price_display' => 'Request Quote',
                    'summary' => 'Sample itinerary preview combining alpine river valleys, Malam Jabba viewpoints, curated tour-only stay options, and flexible fuel plans.',
                    'is_sample' => true,
                ],
                [
                    'title' => 'Work-From-Mountains Skardu & Hunza Add-On',
                    'region' => 'Gilgit-Baltistan',
                    'duration' => '10 Days / 9 Nights',
                    'price_display' => 'Request Quote',
                    'summary' => 'Sample Digital Nomad preview pairing guided northern tours with workspace desk days and transparent power/internet disclosures.',
                    'is_sample' => true,
                ],
            ], 'json'],
            ['footer', 'footer_about', 'Precious Pakistan operates curated domestic and international tourism across all regions of Pakistan with transparent seasonal pricing, company-managed transport, and dedicated booking officers.', 'string'],
            ['footer', 'footer_copyright', 'Precious Pakistan. All rights reserved.', 'string'],
        ];

        foreach ($defaults as [$group, $key, $value, $type]) {
            $settings->set($key, $value, $group, $type, true);
        }
    }
}
