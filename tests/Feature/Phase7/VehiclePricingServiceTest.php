<?php

namespace Tests\Feature\Phase7;

use App\Models\User;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\Region;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleReservation;
use App\Modules\Fleet\Models\VehicleSeasonalRate;
use App\Modules\Fleet\Services\FleetCatalogService;
use App\Modules\Fleet\Services\VehiclePricingService;
use App\Modules\Rbac\Support\RbacCatalog;
use Carbon\CarbonImmutable;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SampleFleetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class VehiclePricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PakistanDestinationsSeeder::class);
        $this->seed(SampleFleetSeeder::class);
    }

    public function test_cross_season_and_cross_year_trips_prorate_daily_with_driver_always_included_zero_fee(): void
    {
        $year = now()->year;
        $prado = Vehicle::query()->where('code', 'FLT-PRADO-01')->firstOrFail();
        $pricing = app(VehiclePricingService::class);

        // Cross-season: May 31 (Standard 34,000) + June 1 (Peak 40,000) + June 2 (Peak 40,000) = 114,000.00 PKR
        $crossSeason = $pricing->quote(
            vehicle: $prado,
            startDate: CarbonImmutable::create($year, 5, 31),
            endDate: CarbonImmutable::create($year, 6, 2),
            fuelMode: VehicleReservation::FUEL_WITH,
            currency: 'PKR',
            partySize: 5,
        );

        $this->assertTrue($crossSeason['driver_included']);
        $this->assertSame('0.00', $crossSeason['driver_charge']);
        $this->assertSame(3, $crossSeason['rental_days']);
        $this->assertSame('114000.00', $crossSeason['vehicle_subtotal']);
        $this->assertSame('0.00', $crossSeason['route_adjustment_amount']);
        $this->assertSame('114000.00', $crossSeason['total_amount']);

        $driverLine = collect($crossSeason['quote_lines'])->firstWhere('code', 'DRIVER_INCLUDED');
        $this->assertNotNull($driverLine);
        $this->assertSame('0.00', $driverLine['line_total']);
        $this->assertTrue($driverLine['included']);

        // Cross-year: Dec 31 (year: without-fuel 22,000) + Jan 1 (nextYear: without-fuel 24,000) = 46,000.00 PKR
        $crossYear = $pricing->quote(
            vehicle: $prado,
            startDate: CarbonImmutable::create($year, 12, 31),
            endDate: CarbonImmutable::create($year + 1, 1, 1),
            fuelMode: VehicleReservation::FUEL_WITHOUT,
            currency: 'PKR',
        );

        $this->assertSame(2, $crossYear['rental_days']);
        $this->assertSame('46000.00', $crossYear['total_amount']);
        $this->assertSame('0.00', $crossYear['driver_charge']);
    }

    public function test_route_fuel_adjustment_applies_only_to_with_fuel_and_never_double_charges_region_plus_destination(): void
    {
        $year = now()->year;
        $prado = Vehicle::query()->where('code', 'FLT-PRADO-01')->firstOrFail();
        $gbRegion = Region::query()->where('slug', 'gilgit-baltistan')->firstOrFail();
        $hunza = Destination::query()->where('slug', 'hunza')->firstOrFail();
        $skardu = Destination::query()->where('slug', 'skardu')->firstOrFail();

        $pricing = app(VehiclePricingService::class);

        // 1) Without fuel on Gilgit-Baltistan / Skardu => ZERO route fuel adjustment!
        $withoutFuel = $pricing->quote(
            vehicle: $prado,
            startDate: CarbonImmutable::create($year, 4, 10),
            endDate: CarbonImmutable::create($year, 4, 12), // 3 days @ 22,000 = 66,000
            fuelMode: VehicleReservation::FUEL_WITHOUT,
            currency: 'PKR',
            regionId: $gbRegion->id,
            destinationId: $skardu->id,
        );
        $this->assertSame('66000.00', $withoutFuel['vehicle_subtotal']);
        $this->assertSame('0.00', $withoutFuel['route_adjustment_amount']);
        $this->assertSame('66000.00', $withoutFuel['total_amount']);

        // 2) With fuel on Hunza (matches GB region fixed_per_trip = 15,000 once): 3 * 34,000 + 15,000 = 117,000.00
        $withFuelRegion = $pricing->quote(
            vehicle: $prado,
            startDate: CarbonImmutable::create($year, 4, 10),
            endDate: CarbonImmutable::create($year, 4, 12),
            fuelMode: VehicleReservation::FUEL_WITH,
            currency: 'PKR',
            regionId: $gbRegion->id,
            destinationId: $hunza->id,
        );
        $this->assertSame('102000.00', $withFuelRegion['vehicle_subtotal']);
        $this->assertSame('15000.00', $withFuelRegion['route_adjustment_amount']);
        $this->assertSame('117000.00', $withFuelRegion['total_amount']);

        // 3) With fuel on Skardu (destination per_day 4,500 * 3 = 13,500 OVERRIDES GB region 15,000 — never stacks both!):
        $withFuelDest = $pricing->quote(
            vehicle: $prado,
            startDate: CarbonImmutable::create($year, 4, 10),
            endDate: CarbonImmutable::create($year, 4, 12),
            fuelMode: VehicleReservation::FUEL_WITH,
            currency: 'PKR',
            regionId: $gbRegion->id,
            destinationId: $skardu->id,
        );
        $this->assertSame('102000.00', $withFuelDest['vehicle_subtotal']);
        $this->assertSame('13500.00', $withFuelDest['route_adjustment_amount']);
        $this->assertSame('115500.00', $withFuelDest['total_amount']);
    }

    public function test_equal_priority_overlapping_rates_and_party_size_overflow_are_rejected(): void
    {
        $fleetManager = User::factory()->staff()->create();
        $fleetManager->assignRole(RbacCatalog::ROLE_FLEET_MANAGER);

        $prado = Vehicle::query()->where('code', 'FLT-PRADO-01')->firstOrFail();

        // Party size 7 exceeds Prado max 6 seats.
        try {
            app(VehiclePricingService::class)->quote(
                vehicle: $prado,
                startDate: CarbonImmutable::create(now()->year, 4, 10),
                endDate: CarbonImmutable::create(now()->year, 4, 11),
                fuelMode: VehicleReservation::FUEL_WITH,
                currency: 'PKR',
                partySize: 7,
            );
            $this->fail('Expected party size overflow to fail.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('seats up to 6', $e->getMessage());
        }

        // Overlapping equal-priority seasonal rates must fail.
        $this->expectException(InvalidArgumentException::class);

        app(FleetCatalogService::class)->saveVehicle($fleetManager, null, [
            'name' => 'Clash Vehicle',
            'slug' => 'clash-vehicle',
            'vehicle_type' => 'sedan',
            'make' => 'Toyota',
            'model' => 'Yaris',
            'passenger_capacity' => 4,
            'seasonal_rates' => [
                ['rate_name' => 'A', 'rate_tier' => VehicleSeasonalRate::TIER_SEASONAL_YEARLY, 'valid_from' => '2027-06-01', 'valid_to' => '2027-07-15', 'priority' => 20, 'with_fuel_rate_pkr' => 20000, 'without_fuel_rate_pkr' => 12000],
                ['rate_name' => 'B', 'rate_tier' => VehicleSeasonalRate::TIER_SEASONAL_YEARLY, 'valid_from' => '2027-07-10', 'valid_to' => '2027-08-01', 'priority' => 20, 'with_fuel_rate_pkr' => 22000, 'without_fuel_rate_pkr' => 14000],
            ],
        ]);
    }
}
