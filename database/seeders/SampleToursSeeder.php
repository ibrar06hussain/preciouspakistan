<?php

namespace Database\Seeders;

use App\Modules\Destinations\Models\Destination;
use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Models\TourDeparture;
use App\Modules\Tours\Services\TourCatalogService;
use Illuminate\Database\Seeder;

class SampleToursSeeder extends Seeder
{
    public function run(TourCatalogService $catalog): void
    {
        $dest = fn (string $slug): ?int => Destination::query()->where('slug', $slug)->value('id');

        $tours = [
            [
                'code' => 'PP-GB-HUNZA-05',
                'title' => 'Hunza, Attabad & Passu Scenic Expedition',
                'slug' => 'hunza-attabad-passu-scenic-expedition',
                'category' => 'sightseeing',
                'difficulty' => 'easy',
                'duration_days' => 5,
                'duration_nights' => 4,
                'min_guests' => 2,
                'max_guests' => 14,
                'booking_lead_days' => 3,
                'pickup_location' => 'Islamabad International Airport (ISB)',
                'dropoff_location' => 'Islamabad',
                'pricing_model' => Tour::PRICING_PER_PERSON,
                'base_price_pkr' => '135000.00',
                'base_price_usd' => '490.00',
                'includes_base_accommodation' => false,
                'requires_company_vehicle' => true,
                'supports_hotel_selection' => true,
                'supports_nomad_addon' => true,
                'is_featured' => true,
                'summary' => 'Sample 5-day northern Pakistan road expedition through Gilgit, Karimabad heritage forts, Attabad Lake, and Passu Cones.',
                'description_html' => '<p>Travel the Karakoram Highway with Precious Pakistan’s company-operated fleet and experienced local driver. Explore Karimabad’s historic forts, turquoise Attabad Lake, and Upper Hunza glacier viewpoints.</p>',
                'highlights' => ['Baltit & Altit Heritage Forts', 'Attabad Lake Boat Stop', 'Passu Cones & Hussaini Bridge', 'Driver-Included Company 4x4 or Grand Cabin'],
                'inclusions' => ['Company vehicle with professional driver', 'Guided sightseeing coordination', 'Pre-verified hotel preference handling'],
                'exclusions' => ['Domestic flights unless quoted', 'Personal meals & park entry tickets'],
                'faqs' => [
                    ['question' => 'Is the driver included in vehicle pricing?', 'answer_html' => '<p>Yes — company drivers are always included at zero additional charge.</p>'],
                ],
                'destination_slugs' => ['islamabad', 'gilgit', 'hunza'],
                'itinerary_days' => [
                    ['day_number' => 1, 'title' => 'Islamabad to Gilgit Scenic Transfer', 'dest' => 'gilgit', 'meals_included' => 'None', 'travel_distance_km' => 510, 'description_html' => '<p>Morning pickup from Islamabad and scenic drive north toward Gilgit.</p>'],
                    ['day_number' => 2, 'title' => 'Gilgit to Karimabad (Hunza Valley)', 'dest' => 'hunza', 'meals_included' => 'Breakfast', 'altitude_meters' => 2438, 'travel_distance_km' => 100, 'description_html' => '<p>Stop at Rakaposhi viewpoint before reaching Karimabad and touring Baltit Fort.</p>'],
                    ['day_number' => 3, 'title' => 'Attabad Lake, Passu Cones & Upper Gojal', 'dest' => 'hunza', 'meals_included' => 'Breakfast', 'altitude_meters' => 2600, 'travel_distance_km' => 95, 'description_html' => '<p>Full-day Upper Hunza excursion covering Attabad Lake, Hussaini Suspension Bridge, and Passu Glacier.</p>'],
                    ['day_number' => 4, 'title' => 'Eagle’s Nest Sunrise & Return to Gilgit', 'dest' => 'gilgit', 'meals_included' => 'Breakfast', 'travel_distance_km' => 105, 'description_html' => '<p>Sunrise panoramic views at Duikar before heading back down the Karakoram corridor.</p>'],
                    ['day_number' => 5, 'title' => 'Gilgit to Islamabad Drop-Off', 'dest' => 'islamabad', 'meals_included' => 'Breakfast', 'travel_distance_km' => 510, 'description_html' => '<p>Return highway journey to Islamabad with evening drop-off.</p>'],
                ],
                'stays' => [
                    ['dest' => 'gilgit', 'check_in_day' => 1, 'nights_count' => 1, 'notes' => 'Gateway overnight stop'],
                    ['dest' => 'hunza', 'check_in_day' => 2, 'nights_count' => 2, 'notes' => 'Karimabad valley stay'],
                    ['dest' => 'gilgit', 'check_in_day' => 4, 'nights_count' => 1, 'notes' => 'Return overnight stop'],
                ],
                'departures' => [
                    ['departure_code' => 'HUNZA-2027-MAY', 'start_date' => now()->addDays(30)->toDateString(), 'end_date' => now()->addDays(34)->toDateString(), 'capacity' => 12, 'status' => TourDeparture::STATUS_OPEN],
                    ['departure_code' => 'HUNZA-2027-JUN', 'start_date' => now()->addDays(60)->toDateString(), 'end_date' => now()->addDays(64)->toDateString(), 'capacity' => 12, 'status' => TourDeparture::STATUS_OPEN],
                ],
            ],
            [
                'code' => 'PP-GB-SKARDU-04',
                'title' => 'Skardu, Shigar Fort & Deosai Highland Trek',
                'slug' => 'skardu-shigar-deosai-highland-trek',
                'category' => 'trekking',
                'difficulty' => 'challenging',
                'duration_days' => 4,
                'duration_nights' => 3,
                'min_guests' => 2,
                'max_guests' => 10,
                'booking_lead_days' => 5,
                'pickup_location' => 'Skardu Airport (KDU)',
                'dropoff_location' => 'Skardu Airport (KDU)',
                'pricing_model' => Tour::PRICING_PER_PERSON,
                'base_price_pkr' => '165000.00',
                'base_price_usd' => '595.00',
                'includes_base_accommodation' => false,
                'requires_company_vehicle' => true,
                'supports_hotel_selection' => true,
                'supports_nomad_addon' => true,
                'is_featured' => true,
                'summary' => 'Sample Baltistan adventure combining Shangrila Lake, Shigar Cold Desert, and high-altitude Deosai Plains.',
                'description_html' => '<p>Designed for active travelers exploring Baltistan’s dramatic landscapes — from Shigar’s restored fort to the Roof of the World at Deosai National Park.</p>',
                'highlights' => ['Lower Kachura Lake', 'Shigar Fort & Cold Desert', 'Deosai Plains Day Excursion'],
                'inclusions' => ['4x4 mountain vehicle with included driver', 'Local Baltistan trekking guide'],
                'exclusions' => ['Airfare to Skardu', 'Personal trekking gear'],
                'destination_slugs' => ['skardu', 'shigar'],
                'itinerary_days' => [
                    ['day_number' => 1, 'title' => 'Skardu Arrival & Kachura Lakes', 'dest' => 'skardu', 'altitude_meters' => 2228, 'description_html' => '<p>Airport meet-and-greet followed by Upper and Lower Kachura Lakes.</p>'],
                    ['day_number' => 2, 'title' => 'Shigar Valley, Cold Desert & Fort', 'dest' => 'shigar', 'altitude_meters' => 2350, 'description_html' => '<p>Explore Sarfaranga Cold Desert dunes and historic Shigar Fort.</p>'],
                    ['day_number' => 3, 'title' => 'Deosai National Park High Plateau', 'dest' => 'skardu', 'altitude_meters' => 4114, 'description_html' => '<p>Full-day 4x4 trek across Deosai Plains and Sheosar Lake.</p>'],
                    ['day_number' => 4, 'title' => 'Kharpocho Viewpoint & Skardu Departure', 'dest' => 'skardu', 'description_html' => '<p>Morning walk toward Kharpocho Fort before airport drop-off.</p>'],
                ],
                'stays' => [
                    ['dest' => 'skardu', 'check_in_day' => 1, 'nights_count' => 2],
                    ['dest' => 'shigar', 'check_in_day' => 3, 'nights_count' => 1],
                ],
                'departures' => [
                    ['departure_code' => 'SKARDU-2027-JUL', 'start_date' => now()->addDays(45)->toDateString(), 'end_date' => now()->addDays(48)->toDateString(), 'capacity' => 10, 'status' => TourDeparture::STATUS_OPEN],
                ],
            ],
            [
                'code' => 'PP-KP-SWAT-03',
                'title' => 'Swat & Malam Jabba Couples Mountain Retreat',
                'slug' => 'swat-malam-jabba-couples-retreat',
                'category' => 'honeymoon',
                'difficulty' => 'easy',
                'duration_days' => 3,
                'duration_nights' => 2,
                'min_guests' => 2,
                'max_guests' => 4,
                'booking_lead_days' => 2,
                'pickup_location' => 'Islamabad',
                'dropoff_location' => 'Islamabad',
                'pricing_model' => Tour::PRICING_FIXED,
                'base_price_pkr' => '180000.00',
                'base_price_usd' => '650.00',
                'includes_base_accommodation' => true,
                'requires_company_vehicle' => true,
                'supports_hotel_selection' => true,
                'supports_nomad_addon' => false,
                'is_featured' => false,
                'summary' => 'Sample private 3-day Swat Valley mountain getaway via the Swat Motorway with Malam Jabba ridge viewpoints.',
                'description_html' => '<p>Relaxing private couples retreat covering Swat’s pine valleys, Malam Jabba hilltop ridge, and Gandhara heritage museums.</p>',
                'highlights' => ['Private Executive Vehicle + Included Driver', 'Malam Jabba Ridge Views', 'Flexible Upgradeable Stay Options'],
                'inclusions' => ['Fixed private package coordination', 'Driver-included vehicle'],
                'exclusions' => ['Chairlift tickets & personal expenses'],
                'destination_slugs' => ['islamabad', 'swat'],
                'itinerary_days' => [
                    ['day_number' => 1, 'title' => 'Islamabad to Mingora & Fizagat', 'dest' => 'swat', 'travel_distance_km' => 245, 'description_html' => '<p>Smooth motorway drive into Swat Valley with riverside evening check-in.</p>'],
                    ['day_number' => 2, 'title' => 'Malam Jabba Mountain Ridge Excursion', 'dest' => 'swat', 'altitude_meters' => 2804, 'description_html' => '<p>Full day exploring Malam Jabba viewpoints and pine slopes.</p>'],
                    ['day_number' => 3, 'title' => 'Swat Museum & Return to Islamabad', 'dest' => 'islamabad', 'travel_distance_km' => 245, 'description_html' => '<p>Brief cultural visit before returning comfortably to Islamabad.</p>'],
                ],
                'stays' => [
                    ['dest' => 'swat', 'check_in_day' => 1, 'nights_count' => 2],
                ],
                'departures' => [],
            ],
            [
                'code' => 'PP-CUSTOM-HERITAGE-04',
                'title' => 'Bespoke Lahore, Mohenjo-daro & Gwadar Heritage Circuit',
                'slug' => 'bespoke-lahore-mohenjo-daro-gwadar-circuit',
                'category' => 'custom',
                'difficulty' => 'moderate',
                'duration_days' => 4,
                'duration_nights' => 3,
                'min_guests' => 1,
                'max_guests' => 16,
                'booking_lead_days' => 7,
                'pickup_location' => 'Lahore / Karachi',
                'dropoff_location' => 'Karachi / Gwadar',
                'pricing_model' => Tour::PRICING_CUSTOM_QUOTE,
                'base_price_pkr' => null,
                'base_price_usd' => null,
                'includes_base_accommodation' => false,
                'requires_company_vehicle' => true,
                'supports_hotel_selection' => true,
                'supports_nomad_addon' => false,
                'is_featured' => true,
                'summary' => 'Sample custom-quoted cross-provincial circuit spanning Mughal Lahore, ancient Mohenjo-daro, and Balochistan’s Makran coast.',
                'description_html' => '<p>Tailored exclusively to your dates, domestic flight preferences, and group size — always quoted transparently in PKR or USD with zero hidden charges.</p>',
                'highlights' => ['Mughal Lahore Heritage', 'UNESCO Mohenjo-daro Ruins', 'Makran Coast & Gwadar Hammerhead'],
                'inclusions' => ['Bespoke itinerary engineering by Precious Pakistan officers'],
                'exclusions' => ['Quoted individually upon request'],
                'destination_slugs' => ['lahore', 'mohenjo-daro', 'gwadar'],
                'itinerary_days' => [
                    ['day_number' => 1, 'title' => 'Lahore Walled City & Badshahi Mosque', 'dest' => 'lahore', 'description_html' => '<p>Guided architectural walk through Lahore Fort and Badshahi Mosque.</p>'],
                    ['day_number' => 2, 'title' => 'Indus Valley Civilization at Mohenjo-daro', 'dest' => 'mohenjo-daro', 'description_html' => '<p>Guided exploration of the Great Bath and Indus archaeological museum.</p>'],
                    ['day_number' => 3, 'title' => 'Makran Coastal Highway to Gwadar', 'dest' => 'gwadar', 'description_html' => '<p>Scenic coastal transit with Hammerhead sunset viewpoints.</p>'],
                    ['day_number' => 4, 'title' => 'Gwadar West Bay & Onward Departure', 'dest' => 'gwadar', 'description_html' => '<p>Morning coastal sightseeing prior to airport transfer.</p>'],
                ],
                'stays' => [
                    ['dest' => 'lahore', 'check_in_day' => 1, 'nights_count' => 1],
                    ['dest' => 'mohenjo-daro', 'check_in_day' => 2, 'nights_count' => 1],
                    ['dest' => 'gwadar', 'check_in_day' => 3, 'nights_count' => 1],
                ],
                'departures' => [],
            ],
        ];

        foreach ($tours as $def) {
            if (Tour::query()->where('slug', $def['slug'])->exists()) {
                continue;
            }

            $destIds = array_values(array_filter(array_map($dest, $def['destination_slugs'])));
            if ($destIds === []) {
                continue;
            }

            $itinerary = array_map(function (array $day) use ($dest): array {
                $day['destination_id'] = isset($day['dest']) ? $dest($day['dest']) : null;
                unset($day['dest']);

                return $day;
            }, $def['itinerary_days']);

            $stays = [];
            foreach ($def['stays'] as $s) {
                $id = $dest($s['dest']);
                if ($id !== null) {
                    $stays[] = [
                        'destination_id' => $id,
                        'check_in_day' => $s['check_in_day'],
                        'nights_count' => $s['nights_count'],
                        'notes' => $s['notes'] ?? null,
                    ];
                }
            }

            $payload = $def;
            $payload['destination_ids'] = $destIds;
            $payload['itinerary_days'] = $itinerary;
            $payload['stays'] = $stays;
            unset($payload['destination_slugs']);

            $tour = $catalog->saveTour(null, null, $payload);
            $catalog->publishTour(null, $tour);
        }
    }
}
