<?php

namespace Database\Seeders;

use App\Modules\Destinations\Models\Destination;
use App\Modules\Hotels\Models\Hotel;
use App\Modules\Hotels\Models\HotelRoomRate;
use App\Modules\Hotels\Models\TourStayHotel;
use App\Modules\Hotels\Services\HotelCatalogService;
use App\Modules\Tours\Models\Tour;
use Illuminate\Database\Seeder;

class SampleHotelsSeeder extends Seeder
{
    public function run(HotelCatalogService $catalog): void
    {
        $year = now()->year;
        $nextYear = $year + 1;

        $hotelsSpec = [
            [
                'slug' => 'karakoram-view-lodge-hunza-sample',
                'name' => 'Karakoram View Lodge (Sample)',
                'dest_slug' => 'hunza',
                'address' => 'Karimabad Upper Terrace, Hunza, Gilgit-Baltistan',
                'lat' => 36.3220,
                'lng' => 74.6640,
                'short_description_html' => '<p>Sample standard mountain lodge in Karimabad offering clear Rakaposhi views and tour-only room allocations.</p>',
                'room_types' => [
                    [
                        'name' => 'Standard Twin Mountain Room',
                        'code' => 'STD_TWIN',
                        'max_occupancy' => 2,
                        'bed_configuration' => '2 Twin Beds',
                        'rates' => [
                            ['rate_name' => "{$year} Standard Year", 'rate_tier' => HotelRoomRate::TIER_STANDARD_YEARLY, 'valid_from' => "{$year}-01-01", 'valid_to' => "{$year}-12-31", 'priority' => 10, 'nightly_rate_pkr' => '18000.00', 'nightly_rate_usd' => '65.00'],
                            ['rate_name' => "{$year} Peak Summer", 'rate_tier' => HotelRoomRate::TIER_SEASONAL_YEARLY, 'valid_from' => "{$year}-06-01", 'valid_to' => "{$year}-08-31", 'priority' => 20, 'nightly_rate_pkr' => '24000.00', 'nightly_rate_usd' => '86.00'],
                            ['rate_name' => "{$nextYear} Standard Year", 'rate_tier' => HotelRoomRate::TIER_STANDARD_YEARLY, 'valid_from' => "{$nextYear}-01-01", 'valid_to' => "{$nextYear}-12-31", 'priority' => 10, 'nightly_rate_pkr' => '19500.00', 'nightly_rate_usd' => '70.00'],
                        ],
                    ],
                    [
                        'name' => 'Family Valley Suite',
                        'code' => 'FAM_SUITE',
                        'max_occupancy' => 4,
                        'bed_configuration' => '1 King + 2 Twins',
                        'rates' => [
                            ['rate_name' => "{$year} Standard Year", 'rate_tier' => HotelRoomRate::TIER_STANDARD_YEARLY, 'valid_from' => "{$year}-01-01", 'valid_to' => "{$year}-12-31", 'priority' => 10, 'nightly_rate_pkr' => '32000.00', 'nightly_rate_usd' => '115.00'],
                            ['rate_name' => "{$nextYear} Standard Year", 'rate_tier' => HotelRoomRate::TIER_STANDARD_YEARLY, 'valid_from' => "{$nextYear}-01-01", 'valid_to' => "{$nextYear}-12-31", 'priority' => 10, 'nightly_rate_pkr' => '34000.00', 'nightly_rate_usd' => '122.00'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'hunza-heritage-palace-retreat-sample',
                'name' => 'Hunza Heritage Terrace Retreat (Sample)',
                'dest_slug' => 'hunza',
                'address' => 'Duikar Ridge Road, Hunza, Gilgit-Baltistan',
                'lat' => 36.3290,
                'lng' => 74.6750,
                'short_description_html' => '<p>Sample premium boutique upgrade property overlooking Hunza Valley peaks, offered exclusively within curated tours.</p>',
                'room_types' => [
                    [
                        'name' => 'Deluxe Glacier Panorama Suite',
                        'code' => 'DLX_GLACIER',
                        'max_occupancy' => 2,
                        'bed_configuration' => '1 King Bed',
                        'rates' => [
                            ['rate_name' => "{$year} Standard Year", 'rate_tier' => HotelRoomRate::TIER_STANDARD_YEARLY, 'valid_from' => "{$year}-01-01", 'valid_to' => "{$year}-12-31", 'priority' => 10, 'nightly_rate_pkr' => '38000.00', 'nightly_rate_usd' => '136.00'],
                            ['rate_name' => "{$year} Peak Summer", 'rate_tier' => HotelRoomRate::TIER_SEASONAL_YEARLY, 'valid_from' => "{$year}-06-01", 'valid_to' => "{$year}-08-31", 'priority' => 20, 'nightly_rate_pkr' => '48000.00', 'nightly_rate_usd' => '172.00'],
                            ['rate_name' => "{$nextYear} Standard Year", 'rate_tier' => HotelRoomRate::TIER_STANDARD_YEARLY, 'valid_from' => "{$nextYear}-01-01", 'valid_to' => "{$nextYear}-12-31", 'priority' => 10, 'nightly_rate_pkr' => '41000.00', 'nightly_rate_usd' => '148.00'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'gilgit-riverfront-inn-sample',
                'name' => 'Gilgit Riverfront Inn (Sample)',
                'dest_slug' => 'gilgit',
                'address' => 'Jutial Riverside Avenue, Gilgit',
                'lat' => 35.9150,
                'lng' => 74.3310,
                'short_description_html' => '<p>Sample transit hotel in Gilgit suited for overnight stops en route to Hunza and Ghizer.</p>',
                'room_types' => [
                    [
                        'name' => 'Executive Double Room',
                        'code' => 'EXEC_DBL',
                        'max_occupancy' => 2,
                        'bed_configuration' => '1 Queen Bed',
                        'rates' => [
                            ['rate_name' => "{$year} Standard Year", 'rate_tier' => HotelRoomRate::TIER_STANDARD_YEARLY, 'valid_from' => "{$year}-01-01", 'valid_to' => "{$year}-12-31", 'priority' => 10, 'nightly_rate_pkr' => '16000.00', 'nightly_rate_usd' => '58.00'],
                            ['rate_name' => "{$nextYear} Standard Year", 'rate_tier' => HotelRoomRate::TIER_STANDARD_YEARLY, 'valid_from' => "{$nextYear}-01-01", 'valid_to' => "{$nextYear}-12-31", 'priority' => 10, 'nightly_rate_pkr' => '17500.00', 'nightly_rate_usd' => '63.00'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'skardu-indus-vista-chalet-sample',
                'name' => 'Skardu Indus Vista Chalet (Sample)',
                'dest_slug' => 'skardu',
                'address' => 'Kachura Road, Skardu, Gilgit-Baltistan',
                'lat' => 35.3010,
                'lng' => 75.6220,
                'short_description_html' => '<p>Sample mountain chalet serving Skardu and Deosai expedition guests.</p>',
                'room_types' => [
                    [
                        'name' => 'Standard Mountain Room',
                        'code' => 'STD_MTN',
                        'max_occupancy' => 2,
                        'bed_configuration' => '2 Twin Beds',
                        'rates' => [
                            ['rate_name' => "{$year} Standard Year", 'rate_tier' => HotelRoomRate::TIER_STANDARD_YEARLY, 'valid_from' => "{$year}-01-01", 'valid_to' => "{$year}-12-31", 'priority' => 10, 'nightly_rate_pkr' => '21000.00', 'nightly_rate_usd' => '75.00'],
                            ['rate_name' => "{$nextYear} Standard Year", 'rate_tier' => HotelRoomRate::TIER_STANDARD_YEARLY, 'valid_from' => "{$nextYear}-01-01", 'valid_to' => "{$nextYear}-12-31", 'priority' => 10, 'nightly_rate_pkr' => '22500.00', 'nightly_rate_usd' => '80.00'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'swat-pine-valley-chalet-sample',
                'name' => 'Swat Pine Valley Chalet (Sample)',
                'dest_slug' => 'swat',
                'address' => 'Fizagat Riverside, Mingora, Swat, KPK',
                'lat' => 34.7890,
                'lng' => 72.3710,
                'short_description_html' => '<p>Sample riverside chalet in Swat Valley offered as included standard lodging on Swat couples tours.</p>',
                'room_types' => [
                    [
                        'name' => 'Standard Riverside Room',
                        'code' => 'STD_RIVER',
                        'max_occupancy' => 2,
                        'bed_configuration' => '1 King Bed',
                        'rates' => [
                            ['rate_name' => "{$year} Standard Year", 'rate_tier' => HotelRoomRate::TIER_STANDARD_YEARLY, 'valid_from' => "{$year}-01-01", 'valid_to' => "{$year}-12-31", 'priority' => 10, 'nightly_rate_pkr' => '15000.00', 'nightly_rate_usd' => '54.00'],
                            ['rate_name' => "{$nextYear} Standard Year", 'rate_tier' => HotelRoomRate::TIER_STANDARD_YEARLY, 'valid_from' => "{$nextYear}-01-01", 'valid_to' => "{$nextYear}-12-31", 'priority' => 10, 'nightly_rate_pkr' => '16000.00', 'nightly_rate_usd' => '58.00'],
                        ],
                    ],
                    [
                        'name' => 'Honeymoon Luxury Suite',
                        'code' => 'LUX_HONEYMOON',
                        'max_occupancy' => 2,
                        'bed_configuration' => '1 King Bed + Balcony',
                        'rates' => [
                            ['rate_name' => "{$year} Standard Year", 'rate_tier' => HotelRoomRate::TIER_STANDARD_YEARLY, 'valid_from' => "{$year}-01-01", 'valid_to' => "{$year}-12-31", 'priority' => 10, 'nightly_rate_pkr' => '28000.00', 'nightly_rate_usd' => '100.00'],
                            ['rate_name' => "{$nextYear} Standard Year", 'rate_tier' => HotelRoomRate::TIER_STANDARD_YEARLY, 'valid_from' => "{$nextYear}-01-01", 'valid_to' => "{$nextYear}-12-31", 'priority' => 10, 'nightly_rate_pkr' => '30000.00', 'nightly_rate_usd' => '108.00'],
                        ],
                    ],
                ],
            ],
        ];

        $created = [];
        foreach ($hotelsSpec as $spec) {
            $destId = Destination::query()->where('slug', $spec['dest_slug'])->value('id');
            if ($destId === null) {
                continue;
            }

            $existing = Hotel::query()->where('slug', $spec['slug'])->first();
            $hotel = $existing ?? $catalog->saveHotel(null, null, [
                'name' => $spec['name'],
                'slug' => $spec['slug'],
                'destination_id' => $destId,
                'address' => $spec['address'],
                'latitude' => $spec['lat'],
                'longitude' => $spec['lng'],
                'short_description_html' => $spec['short_description_html'],
                'status' => Hotel::STATUS_ACTIVE,
                'is_sample_content' => true,
                'room_types' => $spec['room_types'],
            ]);

            $created[$spec['slug']] = $hotel->fresh('roomTypes');
        }

        // Link eligible hotels to seeded overnight tour stays.
        $hunzaTour = Tour::query()->where('slug', 'hunza-attabad-passu-scenic-expedition')->with('stays.destination')->first();
        if ($hunzaTour !== null) {
            foreach ($hunzaTour->stays as $stay) {
                if ($stay->destination?->slug === 'hunza' && isset($created['karakoram-view-lodge-hunza-sample'])) {
                    $defaultHotel = $created['karakoram-view-lodge-hunza-sample'];
                    $catalog->attachHotelToTourStay(null, $stay, $defaultHotel, TourStayHotel::OPTION_DEFAULT, $defaultHotel->roomTypes->first(), 1, 'Standard valley stay');

                    if (isset($created['hunza-heritage-palace-retreat-sample'])) {
                        $upgradeHotel = $created['hunza-heritage-palace-retreat-sample'];
                        $catalog->attachHotelToTourStay(null, $stay, $upgradeHotel, TourStayHotel::OPTION_UPGRADE, $upgradeHotel->roomTypes->first(), 2, 'Boutique panoramic upgrade');
                    }
                }
                if ($stay->destination?->slug === 'gilgit' && isset($created['gilgit-riverfront-inn-sample'])) {
                    $gilgitHotel = $created['gilgit-riverfront-inn-sample'];
                    $catalog->attachHotelToTourStay(null, $stay, $gilgitHotel, TourStayHotel::OPTION_DEFAULT, $gilgitHotel->roomTypes->first(), 1);
                }
            }
        }

        $skarduTour = Tour::query()->where('slug', 'skardu-shigar-deosai-highland-trek')->with('stays.destination')->first();
        if ($skarduTour !== null && isset($created['skardu-indus-vista-chalet-sample'])) {
            foreach ($skarduTour->stays as $stay) {
                if ($stay->destination?->slug === 'skardu') {
                    $h = $created['skardu-indus-vista-chalet-sample'];
                    $catalog->attachHotelToTourStay(null, $stay, $h, TourStayHotel::OPTION_DEFAULT, $h->roomTypes->first(), 1);
                }
            }
        }

        $swatTour = Tour::query()->where('slug', 'swat-malam-jabba-couples-retreat')->with('stays.destination')->first();
        if ($swatTour !== null && isset($created['swat-pine-valley-chalet-sample'])) {
            foreach ($swatTour->stays as $stay) {
                if ($stay->destination?->slug === 'swat') {
                    $h = $created['swat-pine-valley-chalet-sample'];
                    $stdRoom = $h->roomTypes->firstWhere('code', 'STD_RIVER') ?? $h->roomTypes->first();
                    $catalog->attachHotelToTourStay(null, $stay, $h, TourStayHotel::OPTION_DEFAULT, $stdRoom, 1, 'Included standard room; suite upgrade charges delta only');
                }
            }
        }
    }
}
