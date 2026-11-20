<?php

namespace Tests\Feature\Phase7;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleBlackout;
use App\Modules\Fleet\Models\VehicleReservation;
use App\Modules\Fleet\Services\VehicleAvailabilityService;
use App\Modules\Rbac\Support\RbacCatalog;
use Carbon\CarbonImmutable;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SampleFleetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class VehicleAvailabilityAndConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PakistanDestinationsSeeder::class);
        $this->seed(SampleFleetSeeder::class);
    }

    public function test_concurrent_overlapping_vehicle_hold_is_rejected_and_released_or_expired_hold_frees_vehicle(): void
    {
        $officer = User::factory()->staff()->create();
        $officer->assignRole(RbacCatalog::ROLE_BOOKING_OFFICER);

        $prado = Vehicle::query()->where('code', 'FLT-PRADO-01')->firstOrFail();
        $availability = app(VehicleAvailabilityService::class);

        $start = CarbonImmutable::now()->addDays(20)->startOfDay();
        $end = $start->addDays(4);

        $hold = $availability->createHold(
            vehicle: $prado,
            startDate: $start,
            endDate: $end,
            fuelMode: VehicleReservation::FUEL_WITH,
            currency: 'PKR',
            quotedTotal: '170000.00',
            actor: $officer,
        );

        // Overlapping date range on the same vehicle MUST throw RuntimeException.
        try {
            $availability->createHold(
                vehicle: $prado,
                startDate: $start->addDays(2),
                endDate: $end->addDays(2),
                fuelMode: VehicleReservation::FUEL_WITH,
                currency: 'PKR',
                quotedTotal: '170000.00',
                actor: $officer,
            );
            $this->fail('Expected overlapping hold on same vehicle to be rejected.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('unavailable or already assigned', $e->getMessage());
        }

        // Adjacent non-overlapping dates succeed.
        $nextHold = $availability->createHold(
            vehicle: $prado,
            startDate: $end->addDay(),
            endDate: $end->addDays(3),
            fuelMode: VehicleReservation::FUEL_WITHOUT,
            currency: 'PKR',
            quotedTotal: '66000.00',
            actor: $officer,
        );
        $this->assertSame(VehicleReservation::STATUS_HOLD, $nextHold->status);

        // Expiring the first hold frees those exact dates for a new hold.
        $hold->forceFill(['expires_at' => now()->subMinute()])->save();
        $rebooked = $availability->createHold(
            vehicle: $prado,
            startDate: $start,
            endDate: $end,
            fuelMode: VehicleReservation::FUEL_WITH,
            currency: 'PKR',
            quotedTotal: '170000.00',
            actor: $officer,
        );
        $confirmed = $availability->confirmReservation($rebooked, $officer);
        $this->assertSame(VehicleReservation::STATUS_CONFIRMED, $confirmed->status);
    }

    public function test_maintenance_blackout_and_route_filtering_exclude_ineligible_vehicles(): void
    {
        $prado = Vehicle::query()->where('code', 'FLT-PRADO-01')->firstOrFail();
        $availability = app(VehicleAvailabilityService::class);

        $start = CarbonImmutable::now()->addDays(40)->startOfDay();
        $end = $start->addDays(3);

        // Before blackout: Prado matches high_altitude_4x4 for 5 guests.
        $eligible = $availability->eligibleVehicles($start, $end, 5, 'high_altitude_4x4');
        $this->assertSame(['FLT-PRADO-01'], $eligible->pluck('code')->all());

        // Add maintenance blackout overlapping those dates -> Prado is excluded.
        VehicleBlackout::query()->create([
            'vehicle_id' => $prado->id,
            'reason' => 'maintenance',
            'start_date' => $start->addDay()->toDateString(),
            'end_date' => $end->toDateString(),
        ]);

        $eligibleAfterBlackout = $availability->eligibleVehicles($start, $end, 5, 'high_altitude_4x4');
        $this->assertTrue($eligibleAfterBlackout->isEmpty());
    }
}
