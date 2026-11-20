<?php

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleBlackout;
use App\Modules\Fleet\Models\VehicleReservation;
use App\Modules\Rbac\Services\AuditLogger;
use App\Modules\Tours\Models\Tour;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class VehicleAvailabilityService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function isAvailable(
        Vehicle $vehicle,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        ?int $excludeReservationId = null,
    ): bool {
        if ($vehicle->status !== Vehicle::STATUS_ACTIVE) {
            return false;
        }

        $start = CarbonImmutable::instance($startDate)->toDateString();
        $end = CarbonImmutable::instance($endDate)->toDateString();

        $hasBlackout = VehicleBlackout::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();

        if ($hasBlackout) {
            return false;
        }

        $conflictQuery = VehicleReservation::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->where(function ($q): void {
                $q->where('status', VehicleReservation::STATUS_CONFIRMED)
                    ->orWhere(function ($hq): void {
                        $hq->where('status', VehicleReservation::STATUS_HOLD)
                            ->where(function ($eq): void {
                                $eq->whereNull('expires_at')->orWhere('expires_at', '>', now());
                            });
                    });
            });

        if ($excludeReservationId !== null) {
            $conflictQuery->where('id', '!=', $excludeReservationId);
        }

        return ! $conflictQuery->exists();
    }

    /**
     * Filter active company vehicles by party size, date availability, and optional route suitability tag.
     *
     * @return Collection<int, Vehicle>
     */
    public function eligibleVehicles(
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        int $partySize,
        ?string $requiredRouteTag = null,
    ): Collection {
        $candidates = Vehicle::query()
            ->where('status', Vehicle::STATUS_ACTIVE)
            ->where('passenger_capacity', '>=', max(1, $partySize))
            ->orderBy('passenger_capacity')
            ->orderBy('name')
            ->get();

        return $candidates->filter(function (Vehicle $vehicle) use ($startDate, $endDate, $requiredRouteTag): bool {
            if ($requiredRouteTag !== null) {
                $tags = is_array($vehicle->routing_suitability) ? $vehicle->routing_suitability : [];
                if (! in_array($requiredRouteTag, $tags, true)) {
                    return false;
                }
            }

            return $this->isAvailable($vehicle, $startDate, $endDate);
        })->values();
    }

    /**
     * Concurrency-safe transactional hold locking the parent vehicle row to prevent double-booking.
     */
    public function createHold(
        Vehicle $vehicle,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        string $fuelMode,
        string $currency,
        string $quotedTotal,
        ?Tour $tour = null,
        ?int $bookingId = null,
        ?User $actor = null,
        int $holdMinutes = 30,
    ): VehicleReservation {
        $start = CarbonImmutable::instance($startDate)->startOfDay();
        $end = CarbonImmutable::instance($endDate)->startOfDay();

        if ($end->lt($start)) {
            throw new InvalidArgumentException('Reservation end date cannot precede start date.');
        }

        return DB::transaction(function () use ($vehicle, $start, $end, $fuelMode, $currency, $quotedTotal, $tour, $bookingId, $actor, $holdMinutes): VehicleReservation {
            /** @var Vehicle $lockedVehicle */
            $lockedVehicle = Vehicle::query()->lockForUpdate()->findOrFail($vehicle->id);

            // Expire stale holds for this vehicle before checking conflicts.
            VehicleReservation::query()
                ->where('vehicle_id', $lockedVehicle->id)
                ->where('status', VehicleReservation::STATUS_HOLD)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->update(['status' => VehicleReservation::STATUS_RELEASED]);

            if (! $this->isAvailable($lockedVehicle, $start, $end)) {
                throw new RuntimeException(
                    "Vehicle [{$lockedVehicle->name}] is unavailable or already assigned between {$start->toDateString()} and {$end->toDateString()}."
                );
            }

            $reservation = VehicleReservation::query()->create([
                'reference_code' => 'VR-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'vehicle_id' => $lockedVehicle->id,
                'booking_id' => $bookingId,
                'tour_id' => $tour?->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'fuel_mode' => $fuelMode,
                'status' => VehicleReservation::STATUS_HOLD,
                'expires_at' => now()->addMinutes(max(5, $holdMinutes)),
                'quoted_currency' => strtoupper($currency),
                'quoted_total' => number_format((float) $quotedTotal, 2, '.', ''),
                'created_by' => $actor?->id,
            ]);

            if ($actor !== null) {
                $this->audit->record(
                    actor: $actor,
                    event: 'fleet.reservation_hold_created',
                    summary: "Created vehicle hold [{$reservation->reference_code}] for {$lockedVehicle->code}",
                    auditable: $reservation,
                    afterState: ['vehicle_id' => $lockedVehicle->id, 'start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
                );
            }

            return $reservation;
        });
    }

    public function confirmReservation(VehicleReservation $reservation, ?User $actor = null): VehicleReservation
    {
        return DB::transaction(function () use ($reservation, $actor): VehicleReservation {
            $lockedVehicle = Vehicle::query()->lockForUpdate()->findOrFail($reservation->vehicle_id);
            $lockedReservation = VehicleReservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if ($lockedReservation->status === VehicleReservation::STATUS_CONFIRMED) {
                return $lockedReservation;
            }

            if (! $this->isAvailable($lockedVehicle, $lockedReservation->start_date, $lockedReservation->end_date, $lockedReservation->id)) {
                throw new RuntimeException("Cannot confirm reservation [{$lockedReservation->reference_code}]: vehicle conflict detected.");
            }

            $lockedReservation->forceFill([
                'status' => VehicleReservation::STATUS_CONFIRMED,
                'expires_at' => null,
            ])->save();

            if ($actor !== null) {
                $this->audit->record(
                    actor: $actor,
                    event: 'fleet.reservation_confirmed',
                    summary: "Confirmed vehicle reservation [{$lockedReservation->reference_code}]",
                    auditable: $lockedReservation,
                    afterState: ['status' => VehicleReservation::STATUS_CONFIRMED],
                );
            }

            return $lockedReservation->fresh();
        });
    }

    public function releaseReservation(VehicleReservation $reservation, ?User $actor = null): VehicleReservation
    {
        $reservation->forceFill([
            'status' => VehicleReservation::STATUS_RELEASED,
            'expires_at' => null,
        ])->save();

        if ($actor !== null) {
            $this->audit->record(
                actor: $actor,
                event: 'fleet.reservation_released',
                summary: "Released vehicle reservation [{$reservation->reference_code}]",
                auditable: $reservation,
                afterState: ['status' => VehicleReservation::STATUS_RELEASED],
            );
        }

        return $reservation->fresh();
    }
}
