<?php

namespace App\Modules\Nomads\Services;

use App\Modules\Nomads\Models\NomadBlackout;
use App\Modules\Nomads\Models\NomadFacility;
use App\Modules\Nomads\Models\NomadLocation;
use App\Modules\Nomads\Models\NomadSeasonalRate;
use App\Modules\Tours\Models\Tour;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Digital Nomad tour add-on pricing engine.
 * Standalone workspace checkout is strictly disabled unless explicitly authorized via config.
 */
final class NomadPricingService
{
    /**
     * @return array{
     *     location_id: int,
     *     location_name: string,
     *     facility_id: int,
     *     facility_name: string,
     *     unit: string,
     *     quantity: int,
     *     units_per_day: int,
     *     start_date: string,
     *     days_count: int,
     *     currency: string,
     *     total_amount: string,
     *     requires_manual_confirmation: bool,
     *     honesty_notice: string,
     *     daily_breakdown: list<array{date: string, unit_rate: string, line_total: string, rate_name: string}>
     * }
     */
    public function quoteTourAddon(
        ?Tour $tour,
        NomadFacility $facility,
        CarbonInterface $startDate,
        int $daysCount,
        int $quantity,
        string $unit = 'day',
        int $hoursPerDay = 1,
        string $currency = 'PKR',
    ): array {
        if ($tour === null && ! config('precious.nomads.allow_standalone_booking', false)) {
            throw new InvalidArgumentException('Standalone Digital Nomad workspace checkout is disabled. Workspaces must be booked as a tour add-on.');
        }

        if ($tour !== null && ! $tour->supports_nomad_addon) {
            throw new InvalidArgumentException("Tour [{$tour->title}] is not enabled for Digital Nomad workspace add-ons.");
        }

        $location = $facility->location;
        if ($location === null || $location->status !== NomadLocation::STATUS_ACTIVE || ! $facility->is_active) {
            throw new InvalidArgumentException('Selected Digital Nomad workspace facility is not currently active.');
        }

        if ($tour !== null) {
            $tourDestIds = $tour->destinations()->pluck('geo_destinations.id')->all();
            if ($tourDestIds !== [] && ! in_array($location->destination_id, $tourDestIds, true)) {
                throw new InvalidArgumentException("Workspace [{$location->name}] is not located at a destination visited by this tour.");
            }
        }

        if ($quantity < 1 || $quantity > $facility->seat_capacity) {
            throw new InvalidArgumentException("Requested seats/rooms ({$quantity}) exceeds facility capacity ({$facility->seat_capacity}).");
        }

        if ($daysCount < 1) {
            throw new InvalidArgumentException('Workspace days count must be at least 1.');
        }

        $unit = strtolower(trim($unit));
        if (! in_array($unit, ['day', 'hour'], true)) {
            throw new InvalidArgumentException('Workspace charge unit must be day or hour.');
        }

        if ($unit === 'day' && ! $facility->allows_daily) {
            throw new InvalidArgumentException('Selected workspace facility does not support daily booking.');
        }

        if ($unit === 'hour' && (! $facility->allows_hourly || $hoursPerDay < 1 || $hoursPerDay > 16)) {
            throw new InvalidArgumentException('Invalid hourly workspace configuration.');
        }

        $currency = strtoupper(trim($currency));
        if (! in_array($currency, ['PKR', 'USD'], true)) {
            throw new InvalidArgumentException("Unsupported currency [{$currency}].");
        }

        $start = CarbonImmutable::instance($startDate)->startOfDay();
        $end = $start->addDays($daysCount - 1);

        $blackoutExists = NomadBlackout::query()
            ->where('nomad_location_id', $location->id)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->exists();

        if ($blackoutExists) {
            throw new InvalidArgumentException("Workspace [{$location->name}] is unavailable due to a scheduled blackout during selected dates.");
        }

        $column = ($unit === 'hour' ? 'hourly_rate_' : 'daily_rate_').strtolower($currency);
        $multiplierPerDay = $unit === 'hour' ? ($quantity * $hoursPerDay) : $quantity;

        $total = '0.00';
        $breakdown = [];

        for ($i = 0; $i < $daysCount; $i++) {
            $date = $start->addDays($i);
            $dateStr = $date->toDateString();

            $rate = NomadSeasonalRate::query()
                ->where('nomad_facility_id', $facility->id)
                ->where('is_active', true)
                ->whereNotNull($column)
                ->where($column, '>', 0)
                ->whereDate('valid_from', '<=', $dateStr)
                ->whereDate('valid_to', '>=', $dateStr)
                ->get()
                ->sortByDesc(fn (NomadSeasonalRate $r): int => ($r->tierPrecedence() * 10000) + $r->priority)
                ->first();

            if ($rate === null) {
                throw new InvalidArgumentException("Missing {$currency} {$unit} rate for [{$facility->name}] on {$dateStr}.");
            }

            $unitRate = number_format((float) $rate->{$column}, 2, '.', '');
            $lineTotal = bcmul($unitRate, (string) $multiplierPerDay, 2);
            $total = bcadd($total, $lineTotal, 2);

            $breakdown[] = [
                'date' => $dateStr,
                'unit_rate' => $unitRate,
                'line_total' => $lineTotal,
                'rate_name' => $rate->rate_name,
            ];
        }

        return [
            'location_id' => $location->id,
            'location_name' => $location->name,
            'facility_id' => $facility->id,
            'facility_name' => $facility->name,
            'unit' => $unit,
            'quantity' => $quantity,
            'units_per_day' => $unit === 'hour' ? $hoursPerDay : 1,
            'start_date' => $start->toDateString(),
            'days_count' => $daysCount,
            'currency' => $currency,
            'total_amount' => $total,
            'requires_manual_confirmation' => (bool) $facility->requires_manual_confirmation,
            'honesty_notice' => $location->honestyNotice(),
            'daily_breakdown' => $breakdown,
        ];
    }
}
