<?php

namespace Database\Seeders;

use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\Region;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleRouteAdjustment;
use App\Modules\Fleet\Models\VehicleSeasonalRate;
use App\Modules\Fleet\Services\FleetCatalogService;
use Illuminate\Database\Seeder;

class SampleFleetSeeder extends Seeder
{
    public function run(FleetCatalogService $catalog): void
    {
        $year = now()->year;
        $nextYear = $year + 1;

        $gbRegionId = Region::query()->where('slug', 'gilgit-baltistan')->value('id');
        $skarduDestId = Destination::query()->where('slug', 'skardu')->value('id');

        $fleetSpec = [
            [
                'code' => 'FLT-PRADO-01',
                'name' => 'Toyota Land Cruiser Prado 4x4 (Sample)',
                'slug' => 'toyota-land-cruiser-prado-4x4-sample',
                'vehicle_type' => 'suv_4x4',
                'make' => 'Toyota',
                'model' => 'Land Cruiser Prado TX',
                'model_year' => 2022,
                'registration_number' => 'ICT-PP-401',
                'internal_fleet_tag' => 'NORTH-4X4-01',
                'passenger_capacity' => 6,
                'luggage_capacity' => 5,
                'transmission' => 'automatic',
                'has_ac' => true,
                'amenities' => ['Dual-Zone A/C', '4WD High-Altitude Ready', 'First-Aid Kit', 'USB Charging'],
                'routing_suitability' => ['highway', 'karakoram', 'high_altitude_4x4', 'city'],
                'summary' => 'Sample flagship 4x4 mountain SUV with included senior Karakoram driver, ideal for Hunza, Skardu, and Deosai.',
                'driver_assignments' => [
                    ['driver_name' => 'Javed Iqbal (Sample Driver)', 'driver_phone' => '+923000000401', 'license_number' => 'LTV-ISB-401', 'assigned_from' => "{$year}-01-01", 'is_primary' => true],
                ],
                'seasonal_rates' => [
                    [
                        'rate_name' => "{$year} Standard Year",
                        'rate_tier' => VehicleSeasonalRate::TIER_STANDARD_YEARLY,
                        'valid_from' => "{$year}-01-01",
                        'valid_to' => "{$year}-12-31",
                        'priority' => 10,
                        'with_fuel_rate_pkr' => '34000.00',
                        'without_fuel_rate_pkr' => '22000.00',
                        'with_fuel_rate_usd' => '122.00',
                        'without_fuel_rate_usd' => '80.00',
                        'daily_mileage_allowance_km' => 220,
                        'excess_km_rate_pkr' => '95.00',
                        'excess_km_rate_usd' => '0.35',
                        'excess_km_terms' => 'PKR 95/km beyond 220 km/day average on with-fuel plans.',
                    ],
                    [
                        'rate_name' => "{$year} Peak Summer",
                        'rate_tier' => VehicleSeasonalRate::TIER_SEASONAL_YEARLY,
                        'valid_from' => "{$year}-06-01",
                        'valid_to' => "{$year}-08-31",
                        'priority' => 20,
                        'with_fuel_rate_pkr' => '40000.00',
                        'without_fuel_rate_pkr' => '27000.00',
                        'with_fuel_rate_usd' => '144.00',
                        'without_fuel_rate_usd' => '97.00',
                        'daily_mileage_allowance_km' => 220,
                        'excess_km_rate_pkr' => '110.00',
                        'excess_km_terms' => 'PKR 110/km beyond 220 km/day average in peak season.',
                    ],
                    [
                        'rate_name' => "{$nextYear} Standard Year",
                        'rate_tier' => VehicleSeasonalRate::TIER_STANDARD_YEARLY,
                        'valid_from' => "{$nextYear}-01-01",
                        'valid_to' => "{$nextYear}-12-31",
                        'priority' => 10,
                        'with_fuel_rate_pkr' => '36000.00',
                        'without_fuel_rate_pkr' => '24000.00',
                        'with_fuel_rate_usd' => '130.00',
                        'without_fuel_rate_usd' => '86.00',
                        'daily_mileage_allowance_km' => 220,
                        'excess_km_rate_pkr' => '100.00',
                    ],
                ],
                'route_adjustments' => array_values(array_filter([
                    $gbRegionId ? [
                        'region_id' => $gbRegionId,
                        'destination_id' => null,
                        'route_label' => 'Gilgit-Baltistan High-Altitude Fuel Surcharge',
                        'adjustment_mode' => VehicleRouteAdjustment::MODE_FIXED_PER_TRIP,
                        'amount_pkr' => '15000.00',
                        'amount_usd' => '54.00',
                        'is_active' => true,
                    ] : null,
                    $skarduDestId ? [
                        'region_id' => $gbRegionId,
                        'destination_id' => $skarduDestId,
                        'route_label' => 'Skardu & Deosai Mountain Climb Adjustment',
                        'adjustment_mode' => VehicleRouteAdjustment::MODE_PER_DAY,
                        'amount_pkr' => '4500.00',
                        'amount_usd' => '16.00',
                        'is_active' => true,
                    ] : null,
                ])),
            ],
            [
                'code' => 'FLT-HIACE-02',
                'name' => 'Toyota HiAce Grand Cabin (Sample)',
                'slug' => 'toyota-hiace-grand-cabin-sample',
                'vehicle_type' => 'grand_cabin',
                'make' => 'Toyota',
                'model' => 'HiAce High-Roof Grand Cabin',
                'model_year' => 2023,
                'registration_number' => 'ICT-PP-402',
                'internal_fleet_tag' => 'GROUP-VAN-02',
                'passenger_capacity' => 13,
                'luggage_capacity' => 12,
                'transmission' => 'manual',
                'has_ac' => true,
                'amenities' => ['Dual A/C', 'Roof Luggage Rack', 'Reclining Group Seats'],
                'routing_suitability' => ['highway', 'karakoram', 'city'],
                'summary' => 'Sample 13-passenger group tour van with professional driver included for families and corporate tours.',
                'driver_assignments' => [
                    ['driver_name' => 'Kamran Shah (Sample Driver)', 'driver_phone' => '+923000000402', 'license_number' => 'HTV-ISB-402', 'assigned_from' => "{$year}-01-01", 'is_primary' => true],
                ],
                'seasonal_rates' => [
                    [
                        'rate_name' => "{$year} Standard Year",
                        'rate_tier' => VehicleSeasonalRate::TIER_STANDARD_YEARLY,
                        'valid_from' => "{$year}-01-01",
                        'valid_to' => "{$year}-12-31",
                        'priority' => 10,
                        'with_fuel_rate_pkr' => '32000.00',
                        'without_fuel_rate_pkr' => '19000.00',
                        'with_fuel_rate_usd' => '115.00',
                        'without_fuel_rate_usd' => '68.00',
                        'daily_mileage_allowance_km' => 250,
                        'excess_km_rate_pkr' => '85.00',
                    ],
                    [
                        'rate_name' => "{$nextYear} Standard Year",
                        'rate_tier' => VehicleSeasonalRate::TIER_STANDARD_YEARLY,
                        'valid_from' => "{$nextYear}-01-01",
                        'valid_to' => "{$nextYear}-12-31",
                        'priority' => 10,
                        'with_fuel_rate_pkr' => '34000.00',
                        'without_fuel_rate_pkr' => '20500.00',
                        'with_fuel_rate_usd' => '122.00',
                        'without_fuel_rate_usd' => '74.00',
                        'daily_mileage_allowance_km' => 250,
                    ],
                ],
                'route_adjustments' => [],
            ],
            [
                'code' => 'FLT-SEDAN-03',
                'name' => 'Toyota Corolla Altis Sedan (Sample)',
                'slug' => 'toyota-corolla-altis-sedan-sample',
                'vehicle_type' => 'sedan',
                'make' => 'Toyota',
                'model' => 'Corolla Altis 1.6',
                'model_year' => 2024,
                'registration_number' => 'ICT-PP-403',
                'internal_fleet_tag' => 'CITY-SEDAN-03',
                'passenger_capacity' => 4,
                'luggage_capacity' => 3,
                'transmission' => 'automatic',
                'has_ac' => true,
                'amenities' => ['Climate Control A/C', 'Highway Comfort Suspension'],
                'routing_suitability' => ['city', 'highway', 'coastal'],
                'summary' => 'Sample executive sedan with included driver for Islamabad, Lahore, Murree, Swat, and coastal highways.',
                'driver_assignments' => [
                    ['driver_name' => 'Waqas Ali (Sample Driver)', 'driver_phone' => '+923000000403', 'license_number' => 'LTV-ISB-403', 'assigned_from' => "{$year}-01-01", 'is_primary' => true],
                ],
                'seasonal_rates' => [
                    [
                        'rate_name' => "{$year} Standard Year",
                        'rate_tier' => VehicleSeasonalRate::TIER_STANDARD_YEARLY,
                        'valid_from' => "{$year}-01-01",
                        'valid_to' => "{$year}-12-31",
                        'priority' => 10,
                        'with_fuel_rate_pkr' => '18000.00',
                        'without_fuel_rate_pkr' => '11000.00',
                        'with_fuel_rate_usd' => '65.00',
                        'without_fuel_rate_usd' => '40.00',
                        'daily_mileage_allowance_km' => 250,
                    ],
                    [
                        'rate_name' => "{$nextYear} Standard Year",
                        'rate_tier' => VehicleSeasonalRate::TIER_STANDARD_YEARLY,
                        'valid_from' => "{$nextYear}-01-01",
                        'valid_to' => "{$nextYear}-12-31",
                        'priority' => 10,
                        'with_fuel_rate_pkr' => '19500.00',
                        'without_fuel_rate_pkr' => '12000.00',
                        'with_fuel_rate_usd' => '70.00',
                        'without_fuel_rate_usd' => '43.00',
                        'daily_mileage_allowance_km' => 250,
                    ],
                ],
                'route_adjustments' => [],
            ],
        ];

        foreach ($fleetSpec as $spec) {
            if (Vehicle::query()->where('code', $spec['code'])->exists()) {
                continue;
            }

            $catalog->saveVehicle(null, null, $spec);
        }
    }
}
