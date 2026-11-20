<?php

namespace App\Modules\Fleet\Services;

use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleReservation;
use App\Modules\Fleet\Models\VehicleRouteAdjustment;
use App\Modules\Fleet\Models\VehicleSeasonalRate;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Standalone, immutable DECIMAL pricing engine for Precious Pakistan company fleet.
 *
 * Confirmed Business Invariants:
 * 1. Driver is ALWAYS included (`driver_included = true`, `driver_charge = '0.00'`). Never charged separately.
 * 2. Trips crossing seasons or years are split and prorated day-by-day using exact `bcmath` DECIMAL arithmetic.
 * 3. `without_fuel` uses `without_fuel_rate_*` and NEVER applies a route fuel adjustment.
 * 4. `with_fuel` uses `with_fuel_rate_*` and applies AT MOST ONE highest-specificity active route adjustment
 *    (destination-specific overrides region-specific), configured as `fixed_per_trip` OR `per_day` — zero double-charging.
 */
final class VehiclePricingService
{
    /**
     * @return array{
     *     vehicle_id: int,
     *     vehicle_code: string,
     *     vehicle_name: string,
     *     start_date: string,
     *     end_date: string,
     *     rental_days: int,
     *     fuel_mode: string,
     *     currency: string,
     *     driver_included: bool,
     *     driver_charge: string,
     *     vehicle_subtotal: string,
     *     route_adjustment_amount: string,
     *     route_adjustment_detail: ?array{id: int, route_label: string, mode: string, unit_amount: string, total_amount: string},
     *     total_amount: string,
     *     mileage_terms: list<string>,
     *     daily_breakdown: list<array{date: string, rate_id: int, rate_name: string, rate_tier: string, daily_rate: string, mileage_allowance_km: ?int}>,
     *     quote_lines: list<array{code: string, label: string, unit: string, quantity: int, unit_price: string, line_total: string, included: bool}>
     * }
     */
    public function quote(
        Vehicle $vehicle,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        string $fuelMode,
        string $currency = 'PKR',
        ?int $regionId = null,
        ?int $destinationId = null,
        ?int $partySize = null,
    ): array {
        if ($vehicle->status !== Vehicle::STATUS_ACTIVE) {
            throw new InvalidArgumentException("Vehicle [{$vehicle->name}] is not currently active.");
        }

        if (! in_array($fuelMode, [VehicleReservation::FUEL_WITH, VehicleReservation::FUEL_WITHOUT], true)) {
            throw new InvalidArgumentException("Invalid fuel mode [{$fuelMode}]. Expected with_fuel or without_fuel.");
        }

        $currency = strtoupper(trim($currency));
        if (! in_array($currency, ['PKR', 'USD'], true)) {
            throw new InvalidArgumentException("Unsupported currency [{$currency}].");
        }

        if ($partySize !== null && ($partySize < 1 || $partySize > $vehicle->passenger_capacity)) {
            throw new InvalidArgumentException(
                "Vehicle [{$vehicle->name}] seats up to {$vehicle->passenger_capacity} passenger(s); cannot accommodate {$partySize}."
            );
        }

        $start = CarbonImmutable::instance($startDate)->startOfDay();
        $end = CarbonImmutable::instance($endDate)->startOfDay();

        if ($end->lt($start)) {
            throw new InvalidArgumentException('Vehicle end date cannot be earlier than start date.');
        }

        $rentalDays = (int) $start->diffInDays($end) + 1;
        $dailyBreakdown = [];
        $mileageTerms = [];
        $vehicleSubtotal = '0.00';

        // Group contiguous identical rate periods cleanly for invoice/quote line clarity.
        $groupedPeriods = [];

        for ($i = 0; $i < $rentalDays; $i++) {
            $date = $start->addDays($i);
            $rate = $this->resolveRateForDate($vehicle, $date, $fuelMode, $currency);

            if ($rate === null) {
                throw new InvalidArgumentException(
                    "Missing active {$currency} {$fuelMode} daily rate for vehicle [{$vehicle->name}] on {$date->toDateString()}."
                );
            }

            $dailyRate = $this->extractRateAmount($rate, $fuelMode, $currency);
            $vehicleSubtotal = bcadd($vehicleSubtotal, $dailyRate, 2);

            $dailyBreakdown[] = [
                'date' => $date->toDateString(),
                'rate_id' => $rate->id,
                'rate_name' => $rate->rate_name,
                'rate_tier' => $rate->rate_tier,
                'daily_rate' => $dailyRate,
                'mileage_allowance_km' => $rate->daily_mileage_allowance_km,
            ];

            if ($rate->daily_mileage_allowance_km !== null || filled($rate->excess_km_terms)) {
                $term = trim(
                    ($rate->daily_mileage_allowance_km ? "{$rate->daily_mileage_allowance_km} km/day allowance" : '')
                    .(filled($rate->excess_km_terms) ? ' — '.$rate->excess_km_terms : '')
                );
                if ($term !== '' && ! in_array($term, $mileageTerms, true)) {
                    $mileageTerms[] = $term;
                }
            }

            $groupKey = $rate->id.'|'.$dailyRate;
            if (! isset($groupedPeriods[$groupKey])) {
                $groupedPeriods[$groupKey] = [
                    'rate_name' => $rate->rate_name,
                    'unit_price' => $dailyRate,
                    'days' => 0,
                    'from' => $date->toDateString(),
                    'to' => $date->toDateString(),
                ];
            }
            $groupedPeriods[$groupKey]['days']++;
            $groupedPeriods[$groupKey]['to'] = $date->toDateString();
        }

        $fuelLabel = $fuelMode === VehicleReservation::FUEL_WITH ? 'With Fuel' : 'Without Fuel';
        $quoteLines = [];

        foreach ($groupedPeriods as $period) {
            $periodTotal = bcmul($period['unit_price'], (string) $period['days'], 2);
            $quoteLines[] = [
                'code' => 'VEHICLE_RENTAL',
                'label' => "{$vehicle->name} ({$fuelLabel} — {$period['rate_name']}: {$period['from']} to {$period['to']})",
                'unit' => 'day',
                'quantity' => $period['days'],
                'unit_price' => $period['unit_price'],
                'line_total' => $periodTotal,
                'included' => false,
            ];
        }

        // Driver is ALWAYS included at zero additional charge.
        $quoteLines[] = [
            'code' => 'DRIVER_INCLUDED',
            'label' => 'Professional Company Driver (Included)',
            'unit' => 'included',
            'quantity' => 1,
            'unit_price' => '0.00',
            'line_total' => '0.00',
            'included' => true,
        ];

        // Route-specific fuel adjustment applies ONLY to `with_fuel` bookings (at most 1 adjustment — zero double-charge).
        $routeAdjustmentAmount = '0.00';
        $routeAdjustmentDetail = null;

        if ($fuelMode === VehicleReservation::FUEL_WITH && ($destinationId !== null || $regionId !== null)) {
            $adjustment = $this->resolveSingleRouteAdjustment($vehicle, $start, $currency, $regionId, $destinationId);

            if ($adjustment !== null) {
                $unitAdj = $currency === 'USD'
                    ? number_format((float) $adjustment->amount_usd, 2, '.', '')
                    : number_format((float) $adjustment->amount_pkr, 2, '.', '');

                $isPerDay = $adjustment->adjustment_mode === VehicleRouteAdjustment::MODE_PER_DAY;
                $adjQty = $isPerDay ? $rentalDays : 1;
                $routeAdjustmentAmount = $isPerDay ? bcmul($unitAdj, (string) $rentalDays, 2) : $unitAdj;

                $routeAdjustmentDetail = [
                    'id' => $adjustment->id,
                    'route_label' => $adjustment->route_label,
                    'mode' => $adjustment->adjustment_mode,
                    'unit_amount' => $unitAdj,
                    'total_amount' => $routeAdjustmentAmount,
                ];

                $quoteLines[] = [
                    'code' => 'ROUTE_FUEL_ADJUSTMENT',
                    'label' => "Route Fuel Adjustment: {$adjustment->route_label}",
                    'unit' => $isPerDay ? 'day' : 'fixed',
                    'quantity' => $adjQty,
                    'unit_price' => $unitAdj,
                    'line_total' => $routeAdjustmentAmount,
                    'included' => false,
                ];
            }
        }

        $totalAmount = bcadd($vehicleSubtotal, $routeAdjustmentAmount, 2);

        return [
            'vehicle_id' => $vehicle->id,
            'vehicle_code' => $vehicle->code,
            'vehicle_name' => $vehicle->name,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'rental_days' => $rentalDays,
            'fuel_mode' => $fuelMode,
            'currency' => $currency,
            'driver_included' => true,
            'driver_charge' => '0.00',
            'vehicle_subtotal' => $vehicleSubtotal,
            'route_adjustment_amount' => $routeAdjustmentAmount,
            'route_adjustment_detail' => $routeAdjustmentDetail,
            'total_amount' => $totalAmount,
            'mileage_terms' => $mileageTerms,
            'daily_breakdown' => $dailyBreakdown,
            'quote_lines' => $quoteLines,
        ];
    }

    public function resolveRateForDate(Vehicle $vehicle, CarbonInterface $date, string $fuelMode, string $currency): ?VehicleSeasonalRate
    {
        $column = $this->rateColumn($fuelMode, $currency);
        $dateStr = $date->toDateString();

        $candidates = VehicleSeasonalRate::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('is_active', true)
            ->whereNotNull($column)
            ->where($column, '>', 0)
            ->whereDate('valid_from', '<=', $dateStr)
            ->whereDate('valid_to', '>=', $dateStr)
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->sortByDesc(fn (VehicleSeasonalRate $r): int => ($r->tierPrecedence() * 10000) + $r->priority)
            ->first();
    }

    private function resolveSingleRouteAdjustment(
        Vehicle $vehicle,
        CarbonInterface $startDate,
        string $currency,
        ?int $regionId,
        ?int $destinationId,
    ): ?VehicleRouteAdjustment {
        $column = $currency === 'USD' ? 'amount_usd' : 'amount_pkr';
        $dateStr = $startDate->toDateString();

        $baseQuery = VehicleRouteAdjustment::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('is_active', true)
            ->whereNotNull($column)
            ->where($column, '>', 0)
            ->where(function ($q) use ($dateStr): void {
                $q->whereNull('valid_from')->orWhereDate('valid_from', '<=', $dateStr);
            })
            ->where(function ($q) use ($dateStr): void {
                $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $dateStr);
            });

        // Highest specificity first: exact destination match overrides region-level match (prevents double-charging).
        if ($destinationId !== null) {
            $destMatch = (clone $baseQuery)->where('destination_id', $destinationId)->first();
            if ($destMatch !== null) {
                return $destMatch;
            }
        }

        if ($regionId !== null) {
            return (clone $baseQuery)->whereNull('destination_id')->where('region_id', $regionId)->first();
        }

        return null;
    }

    private function rateColumn(string $fuelMode, string $currency): string
    {
        $cur = strtolower($currency);

        return $fuelMode === VehicleReservation::FUEL_WITH
            ? "with_fuel_rate_{$cur}"
            : "without_fuel_rate_{$cur}";
    }

    private function extractRateAmount(VehicleSeasonalRate $rate, string $fuelMode, string $currency): string
    {
        $column = $this->rateColumn($fuelMode, $currency);

        return number_format((float) $rate->{$column}, 2, '.', '');
    }
}
