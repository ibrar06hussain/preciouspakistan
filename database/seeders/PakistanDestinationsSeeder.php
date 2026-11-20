<?php

namespace Database\Seeders;

use App\Modules\Destinations\Models\Attraction;
use App\Modules\Destinations\Models\Country;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\District;
use App\Modules\Destinations\Models\Region;
use Illuminate\Database\Seeder;

/**
 * Seeds Pakistan, all 7 required top-level regions/provinces/territories,
 * and curated authentic destination examples + representative attractions.
 *
 * IMPORTANT: District records seeded here are explicitly marked `is_sample_subset = true`
 * and do NOT claim to be an exhaustive official government administrative census.
 */
class PakistanDestinationsSeeder extends Seeder
{
    public function run(): void
    {
        $pakistan = Country::query()->updateOrCreate(
            ['iso_code' => 'PK'],
            [
                'name' => 'Pakistan',
                'slug' => 'pakistan',
                'is_active' => true,
            ]
        );

        $regionsData = [
            [
                'name' => 'Gilgit-Baltistan',
                'slug' => 'gilgit-baltistan',
                'region_type' => 'autonomous_territory',
                'tagline' => 'Meeting Point of the Karakoram, Himalayas & Hindu Kush',
                'summary' => 'Home to K2, Nanga Parbat, turquoise glacial lakes, ancient forts, and high-altitude Silk Road valleys.',
                'latitude' => 35.8026,
                'longitude' => 74.9832,
                'default_zoom' => 7,
                'sort_order' => 1,
                'destinations' => [
                    ['name' => 'Hunza', 'slug' => 'hunza', 'district' => 'Hunza', 'lat' => 36.3167, 'lng' => 74.6500, 'elev' => 2438, 'season' => 'Apr–Nov', 'featured' => true, 'summary' => 'Iconic Karakoram valley famed for Karimabad, Rakaposhi views, autumn foliage, and heritage forts.'],
                    ['name' => 'Gilgit', 'slug' => 'gilgit', 'district' => 'Gilgit', 'lat' => 35.9208, 'lng' => 74.3144, 'elev' => 1500, 'season' => 'Apr–Oct', 'featured' => true, 'summary' => 'Regional administrative and aviation gateway connecting Hunza, Ghizer, Nagar, and Skardu.'],
                    ['name' => 'Skardu', 'slug' => 'skardu', 'district' => 'Skardu', 'lat' => 35.2971, 'lng' => 75.6333, 'elev' => 2228, 'season' => 'May–Oct', 'featured' => true, 'summary' => 'Indus river valley gateway to Baltoro expeditions, Shangrila Lake, Cold Desert, and Deosai Plains.'],
                    ['name' => 'Ghizer', 'slug' => 'ghizer', 'district' => 'Ghizer', 'lat' => 36.1740, 'lng' => 73.4000, 'elev' => 2100, 'season' => 'May–Oct', 'featured' => false, 'summary' => 'Tranquil emerald-river district home to Phander Valley, Khalti Lake, and Shandur Pass corridor.'],
                    ['name' => 'Nagar', 'slug' => 'nagar', 'district' => 'Nagar', 'lat' => 36.2764, 'lng' => 74.7311, 'elev' => 2400, 'season' => 'May–Oct', 'featured' => false, 'summary' => 'Glacier-carved valley facing Hunza with Hopper Glacier, Rush Lake trek, and Golden Peak panoramas.'],
                    ['name' => 'Astore', 'slug' => 'astore', 'district' => 'Astore', 'lat' => 35.3667, 'lng' => 74.8500, 'elev' => 2600, 'season' => 'Jun–Sep', 'featured' => false, 'summary' => 'Alpine highland valley offering access to Rama Meadow, Minimarg, and the Rupal Face of Nanga Parbat.'],
                    ['name' => 'Shigar', 'slug' => 'shigar', 'district' => 'Shigar', 'lat' => 35.4253, 'lng' => 75.7444, 'elev' => 2350, 'season' => 'May–Oct', 'featured' => false, 'summary' => 'Historic Balti valley featuring the restored Shigar Fort, Sarfaranga Cold Desert, and K2 trailhead.'],
                    ['name' => 'Ghanche', 'slug' => 'ghanche', 'district' => 'Ghanche', 'lat' => 35.1611, 'lng' => 76.3372, 'elev' => 2600, 'season' => 'May–Oct', 'featured' => false, 'summary' => 'Eastern Baltistan district encompassing Khaplu Palace, Chaqchan Mosque, and Hushe Valley peaks.'],
                    ['name' => 'Kharmang', 'slug' => 'kharmang', 'district' => 'Kharmang', 'lat' => 35.0917, 'lng' => 76.0028, 'elev' => 2450, 'season' => 'May–Oct', 'featured' => false, 'summary' => 'Scenic canyon district renowned for Manthokha Waterfall and Indus gorge viewpoints.'],
                ],
            ],
            [
                'name' => 'Azad Jammu & Kashmir',
                'slug' => 'azad-jammu-and-kashmir',
                'region_type' => 'autonomous_territory',
                'tagline' => 'Lush River Valleys, Cedar Forests & Alpine Meadows',
                'summary' => 'Vibrant green valleys fed by the Neelum and Jhelum rivers, offering scenic mountain passes and serene villages.',
                'latitude' => 34.3700,
                'longitude' => 73.4711,
                'default_zoom' => 8,
                'sort_order' => 2,
                'destinations' => [
                    ['name' => 'Neelum', 'slug' => 'neelum', 'district' => 'Neelum', 'lat' => 34.5985, 'lng' => 73.9073, 'elev' => 2100, 'season' => 'Apr–Oct', 'featured' => true, 'summary' => 'Sweeping Himalayan valley stretching through Keran, Sharda, Kel, Arang Kel, and Taobat.'],
                    ['name' => 'Muzaffarabad', 'slug' => 'muzaffarabad', 'district' => 'Muzaffarabad', 'lat' => 34.3700, 'lng' => 73.4711, 'elev' => 737, 'season' => 'Mar–Nov', 'featured' => false, 'summary' => 'Capital city of Azad Jammu & Kashmir at the confluence of the Jhelum and Neelum rivers, near Pir Chinasi.'],
                ],
            ],
            [
                'name' => 'Khyber Pakhtunkhwa',
                'slug' => 'khyber-pakhtunkhwa',
                'region_type' => 'province',
                'tagline' => 'Hindu Kush Passes, Emerald Rivers & Gandhara Heritage',
                'summary' => 'From Swat’s alpine valleys and Kaghan’s glacial lakes to Chitral’s mountain culture and ancient Buddhist sites.',
                'latitude' => 35.2227,
                'longitude' => 72.4258,
                'default_zoom' => 7,
                'sort_order' => 3,
                'destinations' => [
                    ['name' => 'Swat', 'slug' => 'swat', 'district' => 'Swat', 'lat' => 35.2227, 'lng' => 72.4258, 'elev' => 980, 'season' => 'Mar–Nov', 'featured' => true, 'summary' => 'Alpine river valley combining Malam Jabba, Kalam forests, Mahodand Lake, and Gandhara archaeology.'],
                    ['name' => 'Naran', 'slug' => 'naran', 'district' => 'Mansehra', 'lat' => 34.9093, 'lng' => 73.6507, 'elev' => 2409, 'season' => 'May–Sep', 'featured' => true, 'summary' => 'Kaghan Valley summer resort hub leading to Saif-ul-Malook Lake, Lulusar Lake, and Babusar Top.'],
                    ['name' => 'Chitral', 'slug' => 'chitral', 'district' => 'Lower Chitral', 'lat' => 35.8518, 'lng' => 71.7864, 'elev' => 1500, 'season' => 'May–Oct', 'featured' => false, 'summary' => 'Mountain cultural heartland framed by Tirich Mir, Kalash Valleys, and historic Shandur Polo routes.'],
                ],
            ],
            [
                'name' => 'Punjab',
                'slug' => 'punjab',
                'region_type' => 'province',
                'tagline' => 'Mughal Grandeur, Living Culinary Culture & Murree Foothills',
                'summary' => 'Pakistan’s cultural and historic heartland spanning imperial Lahore, Sufi shrines, Salt Range lakes, and cool pine hills.',
                'latitude' => 31.5204,
                'longitude' => 74.3587,
                'default_zoom' => 7,
                'sort_order' => 4,
                'destinations' => [
                    ['name' => 'Lahore', 'slug' => 'lahore', 'district' => 'Lahore', 'lat' => 31.5204, 'lng' => 74.3587, 'elev' => 217, 'season' => 'Oct–Mar', 'featured' => true, 'summary' => 'Historic Mughal capital celebrated for the Walled City, Badshahi Mosque, Lahore Fort, and renowned food heritage.'],
                    ['name' => 'Murree', 'slug' => 'murree', 'district' => 'Murree', 'lat' => 33.9070, 'lng' => 73.3943, 'elev' => 2291, 'season' => 'Year-round', 'featured' => false, 'summary' => 'Pine-forested Himalayan foothill station minutes from Islamabad, popular for summer retreats and winter snow.'],
                ],
            ],
            [
                'name' => 'Sindh',
                'slug' => 'sindh',
                'region_type' => 'province',
                'tagline' => 'Indus Valley Civilization & Arabian Sea Metropolis',
                'summary' => 'Ancient archaeological marvels along the Indus paired with Karachi’s vibrant coastal energy and heritage architecture.',
                'latitude' => 25.8943,
                'longitude' => 68.5247,
                'default_zoom' => 7,
                'sort_order' => 5,
                'destinations' => [
                    ['name' => 'Karachi', 'slug' => 'karachi', 'district' => 'Karachi South', 'lat' => 24.8607, 'lng' => 67.0011, 'elev' => 10, 'season' => 'Nov–Feb', 'featured' => false, 'summary' => 'Pakistan’s coastal metropolis offering Arabian Sea islands, colonial-era landmarks, and diverse coastal cuisine.'],
                    ['name' => 'Mohenjo-daro', 'slug' => 'mohenjo-daro', 'district' => 'Larkana', 'lat' => 27.3292, 'lng' => 68.1389, 'elev' => 52, 'season' => 'Nov–Feb', 'featured' => true, 'summary' => 'UNESCO World Heritage bronze-age city of the Indus Valley Civilization dating back over 4,500 years.'],
                ],
            ],
            [
                'name' => 'Balochistan',
                'slug' => 'balochistan',
                'region_type' => 'province',
                'tagline' => 'Dramatic Makran Coastline, Juniper Forests & Mountain Plateaus',
                'summary' => 'Vast dramatic landscapes from Quetta’s highlands and Ziarat’s ancient junipers to the turquoise coves of Gwadar.',
                'latitude' => 28.4907,
                'longitude' => 65.0958,
                'default_zoom' => 6,
                'sort_order' => 6,
                'destinations' => [
                    ['name' => 'Quetta', 'slug' => 'quetta', 'district' => 'Quetta', 'lat' => 30.1798, 'lng' => 66.9750, 'elev' => 1680, 'season' => 'Apr–Oct', 'featured' => false, 'summary' => 'High-altitude provincial capital ringed by dramatic peaks, Hanna Lake, and vibrant mountain orchards.'],
                    ['name' => 'Ziarat', 'slug' => 'ziarat', 'district' => 'Ziarat', 'lat' => 30.3824, 'lng' => 67.7256, 'elev' => 2543, 'season' => 'May–Sep', 'featured' => false, 'summary' => 'Cool juniper-forest hill sanctuary famed for the historic Quaid-e-Azam Residency and clear starlit skies.'],
                    ['name' => 'Gwadar', 'slug' => 'gwadar', 'district' => 'Gwadar', 'lat' => 25.1264, 'lng' => 62.3225, 'elev' => 15, 'season' => 'Nov–Mar', 'featured' => true, 'summary' => 'Scenic Arabian Sea peninsula featuring Hammerhead cliffs, golden beaches, and the Makran Coastal Highway.'],
                ],
            ],
            [
                'name' => 'Islamabad Capital Territory',
                'slug' => 'islamabad-capital-territory',
                'region_type' => 'federal_territory',
                'tagline' => 'Green Capital & Gateway to Pakistan’s Northern Expeditions',
                'summary' => 'Serene planned capital nestled beneath the Margalla Hills — primary arrival and departure hub for northern Pakistan tours.',
                'latitude' => 33.6844,
                'longitude' => 73.0479,
                'default_zoom' => 10,
                'sort_order' => 7,
                'destinations' => [
                    ['name' => 'Islamabad', 'slug' => 'islamabad', 'district' => 'Islamabad', 'lat' => 33.6844, 'lng' => 73.0479, 'elev' => 540, 'season' => 'Oct–Apr', 'featured' => true, 'summary' => 'Modern capital beneath Margalla Hills National Park featuring Faisal Mosque, Lok Virsa, and seamless airport transfers.'],
                ],
            ],
        ];

        $destinationBySlug = [];

        foreach ($regionsData as $regionDef) {
            $region = Region::query()->updateOrCreate(
                ['slug' => $regionDef['slug']],
                [
                    'country_id' => $pakistan->id,
                    'name' => $regionDef['name'],
                    'region_type' => $regionDef['region_type'],
                    'tagline' => $regionDef['tagline'],
                    'summary' => $regionDef['summary'],
                    'latitude' => $regionDef['latitude'],
                    'longitude' => $regionDef['longitude'],
                    'default_zoom' => $regionDef['default_zoom'],
                    'is_active' => true,
                    'sort_order' => $regionDef['sort_order'],
                    'seo_title' => $regionDef['name'].' Tours & Destinations — Precious Pakistan',
                    'seo_description' => $regionDef['summary'],
                ]
            );

            foreach ($regionDef['destinations'] as $destDef) {
                $districtSlug = $region->slug.'-'.strtolower(str_replace(' ', '-', $destDef['district']));
                $district = District::query()->updateOrCreate(
                    ['slug' => $districtSlug],
                    [
                        'region_id' => $region->id,
                        'name' => $destDef['district'],
                        'latitude' => $destDef['lat'],
                        'longitude' => $destDef['lng'],
                        'is_active' => true,
                        'is_sample_subset' => true,
                    ]
                );

                $destination = Destination::query()->updateOrCreate(
                    ['slug' => $destDef['slug']],
                    [
                        'region_id' => $region->id,
                        'district_id' => $district->id,
                        'name' => $destDef['name'],
                        'tagline' => $region->name.' • '.$destDef['season'],
                        'summary' => $destDef['summary'],
                        'description_html' => '<p>'.e($destDef['summary']).'</p>',
                        'elevation_meters' => $destDef['elev'],
                        'best_season' => $destDef['season'],
                        'latitude' => $destDef['lat'],
                        'longitude' => $destDef['lng'],
                        'is_featured' => $destDef['featured'],
                        'is_active' => true,
                        'is_sample_content' => true,
                        'seo_title' => $destDef['name'].' Travel Guide & Tours — Precious Pakistan',
                        'seo_description' => $destDef['summary'],
                    ]
                );

                $destinationBySlug[$destination->slug] = $destination;
            }
        }

        $attractionsData = [
            ['dest' => 'hunza', 'name' => 'Baltit Fort', 'slug' => 'baltit-fort', 'cat' => 'fort', 'lat' => 36.3250, 'lng' => 74.6697, 'summary' => 'Historic terraced hilltop fort overlooking Karimabad and Ultar Sar.'],
            ['dest' => 'hunza', 'name' => 'Attabad Lake', 'slug' => 'attabad-lake', 'cat' => 'lake', 'lat' => 36.3350, 'lng' => 74.8650, 'summary' => 'Striking turquoise mountain lake nestled in Gojal Valley.'],
            ['dest' => 'hunza', 'name' => 'Passu Cones', 'slug' => 'passu-cones', 'cat' => 'viewpoint', 'lat' => 36.4700, 'lng' => 74.8900, 'summary' => 'Dramatic cathedral-like jagged spires along the Karakoram Highway.'],
            ['dest' => 'skardu', 'name' => 'Lower Kachura (Shangrila) Lake', 'slug' => 'lower-kachura-lake', 'cat' => 'lake', 'lat' => 35.4267, 'lng' => 75.4547, 'summary' => 'Tranquil alpine lake framed by orchards and Baltistan peaks.'],
            ['dest' => 'skardu', 'name' => 'Deosai National Park', 'slug' => 'deosai-national-park', 'cat' => 'park', 'lat' => 35.0250, 'lng' => 75.4667, 'summary' => 'High-altitude plateau famed for summer wildflowers and Himalayan wildlife.'],
            ['dest' => 'shigar', 'name' => 'Shigar Fort', 'slug' => 'shigar-fort', 'cat' => 'fort', 'lat' => 35.4258, 'lng' => 75.7448, 'summary' => '17th-century Balti stone-and-timber heritage fort complex.'],
            ['dest' => 'astore', 'name' => 'Rama Meadow & Lake', 'slug' => 'rama-meadow', 'cat' => 'valley', 'lat' => 35.3310, 'lng' => 74.8120, 'summary' => 'Pine-ringed alpine meadow with sweeping views of Nanga Parbat.'],
            ['dest' => 'neelum', 'name' => 'Arang Kel', 'slug' => 'arang-kel', 'cat' => 'valley', 'lat' => 34.8053, 'lng' => 74.3461, 'summary' => 'Car-free alpine hilltop village above Kel in Upper Neelum Valley.'],
            ['dest' => 'swat', 'name' => 'Malam Jabba', 'slug' => 'malam-jabba', 'cat' => 'viewpoint', 'lat' => 34.7999, 'lng' => 72.5714, 'summary' => 'Hindu Kush mountain ridge known for chairlifts, pine slopes, and winter skiing.'],
            ['dest' => 'naran', 'name' => 'Saif-ul-Malook Lake', 'slug' => 'saif-ul-malook-lake', 'cat' => 'lake', 'lat' => 34.8764, 'lng' => 73.6947, 'summary' => 'Glacial mountain lake beneath Malika Parbat peak in Kaghan Valley.'],
            ['dest' => 'lahore', 'name' => 'Badshahi Mosque & Lahore Fort', 'slug' => 'badshahi-mosque', 'cat' => 'heritage', 'lat' => 31.5881, 'lng' => 74.3095, 'summary' => 'Iconic 17th-century Mughal architectural ensemble in historic Lahore.'],
            ['dest' => 'islamabad', 'name' => 'Faisal Mosque & Margalla Trails', 'slug' => 'faisal-mosque', 'cat' => 'landmark', 'lat' => 33.7295, 'lng' => 73.0372, 'summary' => 'Landmark contemporary mosque set directly against the Margalla Hills.'],
            ['dest' => 'mohenjo-daro', 'name' => 'Great Bath of Mohenjo-daro', 'slug' => 'mohenjo-daro-great-bath', 'cat' => 'heritage', 'lat' => 27.3292, 'lng' => 68.1389, 'summary' => 'Masterpiece of ancient Indus urban engineering and brick masonry.'],
            ['dest' => 'quetta', 'name' => 'Hanna Lake', 'slug' => 'hanna-lake', 'cat' => 'lake', 'lat' => 30.2564, 'lng' => 67.0994, 'summary' => 'Turquoise mountain reservoir framed by rugged Balochistan ridges.'],
            ['dest' => 'ziarat', 'name' => 'Quaid-e-Azam Residency', 'slug' => 'quaid-e-azam-residency-ziarat', 'cat' => 'heritage', 'lat' => 30.3811, 'lng' => 67.7278, 'summary' => 'Historic wooden heritage landmark surrounded by ancient juniper forests.'],
            ['dest' => 'gwadar', 'name' => 'Hammerhead & West Bay', 'slug' => 'gwadar-hammerhead', 'cat' => 'beach', 'lat' => 25.1072, 'lng' => 62.3328, 'summary' => 'Dramatic coastal headland overlooking Gwadar’s twin turquoise bays.'],
        ];

        foreach ($attractionsData as $attr) {
            $dest = $destinationBySlug[$attr['dest']] ?? null;
            if ($dest === null) {
                continue;
            }

            Attraction::query()->updateOrCreate(
                ['slug' => $attr['slug']],
                [
                    'destination_id' => $dest->id,
                    'name' => $attr['name'],
                    'category' => $attr['cat'],
                    'summary' => $attr['summary'],
                    'latitude' => $attr['lat'],
                    'longitude' => $attr['lng'],
                    'is_active' => true,
                    'is_sample_content' => true,
                    'seo_title' => $attr['name'].' — '.$dest->name.', Pakistan',
                    'seo_description' => $attr['summary'],
                ]
            );
        }
    }
}
