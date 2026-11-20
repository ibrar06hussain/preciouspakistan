<?php

namespace Database\Seeders;

use App\Modules\Destinations\Models\Destination;
use App\Modules\Nomads\Models\NomadLocation;
use App\Modules\Nomads\Services\NomadCatalogService;
use Illuminate\Database\Seeder;

class SampleNomadsSeeder extends Seeder
{
    public function run(NomadCatalogService $catalog): void
    {
        $year = now()->year;
        $nextYear = $year + 1;

        $specs = [
            [
                'slug' => 'karimabad-mountain-cowork-hunza-sample',
                'name' => 'Karimabad Mountain Cowork Hub (Sample)',
                'dest_slug' => 'hunza',
                'address' => 'Karimabad Bazaar Terrace, Hunza',
                'power_source' => 'Local Hydro Grid',
                'backup_power_details' => 'Solar Hybrid Inverter + 15kVA Diesel Generator',
                'power_guaranteed' => false,
                'internet_provider' => 'SCO Fiber + Cellular Failover',
                'advertised_download_mbps' => 50,
                'advertised_upload_mbps' => 20,
                'internet_guaranteed' => false,
                'connectivity_warning' => 'Mountain fiber speeds vary during severe weather; cellular failover is available but 100% uptime is not guaranteed.',
                'operating_hours' => '08:00–22:00 PKT',
                'facilities' => [
                    [
                        'name' => 'Hot Desk Seat',
                        'code' => 'HOT_DESK',
                        'facility_type' => 'hot_desk',
                        'seat_capacity' => 16,
                        'allows_daily' => true,
                        'allows_hourly' => true,
                        'rates' => [
                            ['rate_name' => "{$year} Standard", 'valid_from' => "{$year}-01-01", 'valid_to' => "{$year}-12-31", 'daily_rate_pkr' => '3500.00', 'daily_rate_usd' => '13.00', 'hourly_rate_pkr' => '700.00', 'hourly_rate_usd' => '3.00'],
                            ['rate_name' => "{$nextYear} Standard", 'valid_from' => "{$nextYear}-01-01", 'valid_to' => "{$nextYear}-12-31", 'daily_rate_pkr' => '3800.00', 'daily_rate_usd' => '14.00', 'hourly_rate_pkr' => '750.00', 'hourly_rate_usd' => '3.00'],
                        ],
                    ],
                    [
                        'name' => 'Private Video-Call Room',
                        'code' => 'CALL_ROOM',
                        'facility_type' => 'meeting_room',
                        'seat_capacity' => 4,
                        'allows_daily' => true,
                        'allows_hourly' => true,
                        'rates' => [
                            ['rate_name' => "{$year} Standard", 'valid_from' => "{$year}-01-01", 'valid_to' => "{$year}-12-31", 'daily_rate_pkr' => '9500.00', 'daily_rate_usd' => '35.00', 'hourly_rate_pkr' => '1800.00', 'hourly_rate_usd' => '7.00'],
                            ['rate_name' => "{$nextYear} Standard", 'valid_from' => "{$nextYear}-01-01", 'valid_to' => "{$nextYear}-12-31", 'daily_rate_pkr' => '10000.00', 'daily_rate_usd' => '36.00', 'hourly_rate_pkr' => '2000.00', 'hourly_rate_usd' => '7.50'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'skardu-indus-work-lounge-sample',
                'name' => 'Skardu Indus Work Lounge (Sample)',
                'dest_slug' => 'skardu',
                'address' => 'Satellite Town, Skardu',
                'power_source' => 'Regional Grid',
                'backup_power_details' => 'UPS Battery Bank + Standby Generator',
                'power_guaranteed' => false,
                'internet_provider' => 'SCO Fiber',
                'advertised_download_mbps' => 35,
                'advertised_upload_mbps' => 15,
                'internet_guaranteed' => false,
                'operating_hours' => '09:00–21:00 PKT',
                'facilities' => [
                    [
                        'name' => 'Dedicated Desk',
                        'code' => 'DED_DESK',
                        'facility_type' => 'dedicated_desk',
                        'seat_capacity' => 10,
                        'allows_daily' => true,
                        'allows_hourly' => false,
                        'rates' => [
                            ['rate_name' => "{$year} Standard", 'valid_from' => "{$year}-01-01", 'valid_to' => "{$year}-12-31", 'daily_rate_pkr' => '4000.00', 'daily_rate_usd' => '15.00'],
                            ['rate_name' => "{$nextYear} Standard", 'valid_from' => "{$nextYear}-01-01", 'valid_to' => "{$nextYear}-12-31", 'daily_rate_pkr' => '4200.00', 'daily_rate_usd' => '16.00'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($specs as $spec) {
            if (NomadLocation::query()->where('slug', $spec['slug'])->exists()) {
                continue;
            }
            $destId = Destination::query()->where('slug', $spec['dest_slug'])->value('id');
            if ($destId === null) {
                continue;
            }
            $payload = $spec;
            $payload['destination_id'] = $destId;
            unset($payload['dest_slug']);

            $catalog->saveLocation(null, null, $payload);
        }
    }
}
