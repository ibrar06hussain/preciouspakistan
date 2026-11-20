<?php

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Cms\Support\HtmlSanitizerService;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleRouteAdjustment;
use App\Modules\Fleet\Models\VehicleSeasonalRate;
use App\Modules\Rbac\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class FleetCatalogService
{
    public function __construct(
        private readonly HtmlSanitizerService $sanitizer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function saveVehicle(?User $actor, ?Vehicle $existing, array $data): Vehicle
    {
        if ($actor !== null) {
            $editingRates = array_key_exists('seasonal_rates', $data) || array_key_exists('route_adjustments', $data);
            if (! $actor->can('vehicles.manage') && ! ($editingRates && $actor->can('vehicles.rates.manage'))) {
                throw new AuthorizationException('You do not have permission to manage company vehicles.');
            }
            if ($editingRates && ! $actor->can('vehicles.rates.manage')) {
                throw new AuthorizationException('You do not have permission [vehicles.rates.manage] to modify vehicle rates or route adjustments.');
            }
        }

        $name = $this->sanitizer->sanitizePlainText($data['name'] ?? $existing?->name ?? '', 'vehicle.name', 160);
        if ($name === '') {
            throw new InvalidArgumentException('Vehicle name is required.');
        }

        $slug = Str::slug((string) ($data['slug'] ?? $existing?->slug ?? $name));
        $type = (string) ($data['vehicle_type'] ?? $existing?->vehicle_type ?? 'suv_4x4');
        if (! array_key_exists($type, Vehicle::TYPES)) {
            throw new InvalidArgumentException("Invalid vehicle type [{$type}].");
        }

        $passengers = (int) ($data['passenger_capacity'] ?? $existing?->passenger_capacity ?? 4);
        $luggage = max(0, (int) ($data['luggage_capacity'] ?? $existing?->luggage_capacity ?? 4));
        if ($passengers < 1 || $passengers > 60) {
            throw new InvalidArgumentException('Passenger capacity must be between 1 and 60.');
        }

        $status = (string) ($data['status'] ?? $existing?->status ?? Vehicle::STATUS_ACTIVE);
        if (! in_array($status, [Vehicle::STATUS_ACTIVE, Vehicle::STATUS_MAINTENANCE, Vehicle::STATUS_RETIRED], true)) {
            $status = Vehicle::STATUS_ACTIVE;
        }

        $seasonalRates = array_key_exists('seasonal_rates', $data)
            ? $this->validateSeasonalRates((array) $data['seasonal_rates'])
            : null;

        $routeAdjustments = array_key_exists('route_adjustments', $data)
            ? $this->validateRouteAdjustments((array) $data['route_adjustments'])
            : null;

        $drivers = array_key_exists('driver_assignments', $data)
            ? $this->validateDriverAssignments((array) $data['driver_assignments'])
            : null;

        $blackouts = array_key_exists('blackouts', $data)
            ? $this->validateBlackouts((array) $data['blackouts'])
            : null;

        $code = trim((string) ($data['code'] ?? $existing?->code ?? ''));
        if ($code === '') {
            $code = 'FLT-'.strtoupper(Str::random(5));
        }

        $descriptionHtml = filled($data['description_html'] ?? null)
            ? $this->sanitizer->sanitizeRichHtml((string) $data['description_html'], 'vehicle.description_html')
            : $existing?->description_html;

        return DB::transaction(function () use (
            $actor,
            $existing,
            $data,
            $code,
            $name,
            $slug,
            $type,
            $passengers,
            $luggage,
            $status,
            $descriptionHtml,
            $seasonalRates,
            $routeAdjustments,
            $drivers,
            $blackouts,
        ): Vehicle {
            $vehicle = $existing ?? new Vehicle;
            $vehicle->fill([
                'code' => strtoupper(mb_substr($code, 0, 40)),
                'name' => $name,
                'slug' => $slug,
                'vehicle_type' => $type,
                'make' => $this->sanitizer->sanitizePlainText($data['make'] ?? $existing?->make ?? 'Toyota', 'vehicle.make', 80),
                'model' => $this->sanitizer->sanitizePlainText($data['model'] ?? $existing?->model ?? '', 'vehicle.model', 80),
                'model_year' => filled($data['model_year'] ?? null) ? (int) $data['model_year'] : $existing?->model_year,
                'registration_number' => filled($data['registration_number'] ?? null)
                    ? strtoupper($this->sanitizer->sanitizePlainText((string) $data['registration_number'], 'vehicle.registration', 64))
                    : $existing?->registration_number,
                'internal_fleet_tag' => $this->sanitizer->sanitizePlainText($data['internal_fleet_tag'] ?? $existing?->internal_fleet_tag, 'vehicle.tag', 64) ?: null,
                'private_notes' => $data['private_notes'] ?? $existing?->private_notes,
                'passenger_capacity' => $passengers,
                'luggage_capacity' => $luggage,
                'transmission' => in_array($data['transmission'] ?? $existing?->transmission ?? 'automatic', ['automatic', 'manual'], true)
                    ? ($data['transmission'] ?? $existing?->transmission ?? 'automatic')
                    : 'automatic',
                'has_ac' => (bool) ($data['has_ac'] ?? $existing?->has_ac ?? true),
                'amenities' => is_array($data['amenities'] ?? null) ? array_values($data['amenities']) : ($existing?->amenities ?? []),
                'routing_suitability' => is_array($data['routing_suitability'] ?? null) ? array_values($data['routing_suitability']) : ($existing?->routing_suitability ?? ['highway']),
                // Business invariant: driver is ALWAYS included in vehicle rental rates.
                'driver_always_included' => true,
                'status' => $status,
                'summary' => $this->sanitizer->sanitizePlainText($data['summary'] ?? $existing?->summary, 'vehicle.summary', 1000) ?: null,
                'description_html' => $descriptionHtml,
                'hero_image_url' => $this->sanitizer->sanitizeUrl($data['hero_image_url'] ?? $existing?->hero_image_url, 'vehicle.hero_image_url') ?: null,
                'is_sample_content' => (bool) ($data['is_sample_content'] ?? $existing?->is_sample_content ?? true),
                'seo_title' => $this->sanitizer->sanitizePlainText($data['seo_title'] ?? $existing?->seo_title, 'vehicle.seo_title', 180) ?: null,
                'seo_description' => $this->sanitizer->sanitizePlainText($data['seo_description'] ?? $existing?->seo_description, 'vehicle.seo_description', 320) ?: null,
                'canonical_url' => $this->sanitizer->sanitizeUrl($data['canonical_url'] ?? $existing?->canonical_url, 'vehicle.canonical_url') ?: null,
                'noindex' => (bool) ($data['noindex'] ?? $existing?->noindex ?? false),
                'created_by' => $existing?->created_by ?? $actor?->id,
                'updated_by' => $actor?->id ?? $existing?->updated_by,
            ])->save();

            if ($seasonalRates !== null) {
                $vehicle->seasonalRates()->delete();
                foreach ($seasonalRates as $rateRow) {
                    $vehicle->seasonalRates()->create($rateRow);
                }
            }

            if ($routeAdjustments !== null) {
                $vehicle->routeAdjustments()->delete();
                foreach ($routeAdjustments as $adjRow) {
                    $vehicle->routeAdjustments()->create($adjRow);
                }
            }

            if ($drivers !== null) {
                $vehicle->driverAssignments()->delete();
                foreach ($drivers as $driverRow) {
                    $vehicle->driverAssignments()->create($driverRow);
                }
            }

            if ($blackouts !== null) {
                $vehicle->blackouts()->delete();
                foreach ($blackouts as $blackoutRow) {
                    $vehicle->blackouts()->create([
                        ...$blackoutRow,
                        'created_by' => $actor?->id,
                    ]);
                }
            }

            if ($actor !== null) {
                $this->audit->record(
                    actor: $actor,
                    event: $existing === null ? 'fleet.vehicle_created' : 'fleet.vehicle_updated',
                    summary: "Saved company vehicle [{$vehicle->code}] {$vehicle->name}",
                    auditable: $vehicle,
                    afterState: ['code' => $vehicle->code, 'status' => $vehicle->status, 'passenger_capacity' => $vehicle->passenger_capacity],
                );
            }

            return $vehicle->fresh(['seasonalRates', 'routeAdjustments', 'driverAssignments', 'blackouts']);
        });
    }

    /**
     * Copy active seasonal/yearly vehicle rates to a future year while preserving historical rates.
     *
     * @throws AuthorizationException
     */
    public function copyRatesToYear(User $actor, Vehicle $vehicle, int $sourceYear, int $targetYear): int
    {
        if (! $actor->can('vehicles.rates.manage')) {
            throw new AuthorizationException('You do not have permission [vehicles.rates.manage] to copy vehicle rates.');
        }

        if ($targetYear <= $sourceYear || $targetYear > 2100) {
            throw new InvalidArgumentException('Target year must be strictly greater than source year.');
        }

        $delta = $targetYear - $sourceYear;
        $vehicle->load('seasonalRates');

        return DB::transaction(function () use ($actor, $vehicle, $sourceYear, $targetYear, $delta): int {
            $copied = 0;

            foreach ($vehicle->seasonalRates as $rate) {
                if (! $rate->is_active || $rate->valid_from->year !== $sourceYear) {
                    continue;
                }

                $newFrom = $rate->valid_from->addYears($delta);
                $newTo = $rate->valid_to->addYears($delta);

                $clash = VehicleSeasonalRate::query()
                    ->where('vehicle_id', $vehicle->id)
                    ->where('rate_tier', $rate->rate_tier)
                    ->where('priority', $rate->priority)
                    ->whereDate('valid_from', '<=', $newTo->toDateString())
                    ->whereDate('valid_to', '>=', $newFrom->toDateString())
                    ->exists();

                if ($clash) {
                    continue;
                }

                $vehicle->seasonalRates()->create([
                    'rate_name' => str_replace((string) $sourceYear, (string) $targetYear, $rate->rate_name),
                    'rate_tier' => $rate->rate_tier,
                    'valid_from' => $newFrom->toDateString(),
                    'valid_to' => $newTo->toDateString(),
                    'priority' => $rate->priority,
                    'with_fuel_rate_pkr' => $rate->with_fuel_rate_pkr,
                    'without_fuel_rate_pkr' => $rate->without_fuel_rate_pkr,
                    'with_fuel_rate_usd' => $rate->with_fuel_rate_usd,
                    'without_fuel_rate_usd' => $rate->without_fuel_rate_usd,
                    'daily_mileage_allowance_km' => $rate->daily_mileage_allowance_km,
                    'excess_km_rate_pkr' => $rate->excess_km_rate_pkr,
                    'excess_km_rate_usd' => $rate->excess_km_rate_usd,
                    'excess_km_terms' => $rate->excess_km_terms,
                    'is_active' => true,
                ]);
                $copied++;
            }

            $this->audit->record(
                actor: $actor,
                event: 'fleet.rates_copied',
                summary: "Copied {$copied} vehicle rate(s) for [{$vehicle->code}] from {$sourceYear} to {$targetYear}",
                auditable: $vehicle,
                afterState: ['source_year' => $sourceYear, 'target_year' => $targetYear, 'copied' => $copied],
            );

            return $copied;
        });
    }

    /**
     * @param  array<int|string, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function validateSeasonalRates(array $rows): array
    {
        $sanitized = [];

        foreach (array_values($rows) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $from = CarbonImmutable::parse((string) ($row['valid_from'] ?? ''));
            $to = CarbonImmutable::parse((string) ($row['valid_to'] ?? ''));
            if ($to->lt($from)) {
                throw new InvalidArgumentException('Vehicle rate valid_to cannot precede valid_from.');
            }

            $tier = (string) ($row['rate_tier'] ?? VehicleSeasonalRate::TIER_STANDARD_YEARLY);
            if (! array_key_exists($tier, VehicleSeasonalRate::TIER_PRECEDENCE)) {
                throw new InvalidArgumentException("Invalid vehicle rate tier [{$tier}].");
            }

            $priority = max(1, (int) ($row['priority'] ?? 10));
            $withPkr = $this->normalizePositiveDecimal($row['with_fuel_rate_pkr'] ?? null, 'with_fuel_rate_pkr');
            $withoutPkr = $this->normalizePositiveDecimal($row['without_fuel_rate_pkr'] ?? null, 'without_fuel_rate_pkr');
            $withUsd = $this->normalizePositiveDecimal($row['with_fuel_rate_usd'] ?? null, 'with_fuel_rate_usd');
            $withoutUsd = $this->normalizePositiveDecimal($row['without_fuel_rate_usd'] ?? null, 'without_fuel_rate_usd');

            if ($withPkr === null && $withoutPkr === null && $withUsd === null && $withoutUsd === null) {
                throw new InvalidArgumentException('Vehicle seasonal rate must define at least one positive PKR or USD daily rate.');
            }

            if ($withPkr !== null && $withoutPkr !== null && bccomp($withPkr, $withoutPkr, 2) < 0) {
                throw new InvalidArgumentException('With-fuel PKR daily rate cannot be lower than without-fuel PKR daily rate.');
            }

            if ($withUsd !== null && $withoutUsd !== null && bccomp($withUsd, $withoutUsd, 2) < 0) {
                throw new InvalidArgumentException('With-fuel USD daily rate cannot be lower than without-fuel USD daily rate.');
            }

            $isActive = (bool) ($row['is_active'] ?? true);

            if ($isActive) {
                foreach ($sanitized as $existing) {
                    if (
                        $existing['is_active']
                        && $existing['rate_tier'] === $tier
                        && $existing['priority'] === $priority
                        && $from->toDateString() <= $existing['valid_to']
                        && $to->toDateString() >= $existing['valid_from']
                    ) {
                        throw new InvalidArgumentException("Overlapping equal-priority vehicle rates detected for tier [{$tier}] between {$from->toDateString()} and {$to->toDateString()}.");
                    }
                }
            }

            $sanitized[] = [
                'rate_name' => $this->sanitizer->sanitizePlainText($row['rate_name'] ?? 'Seasonal Rate', 'rate.name', 120),
                'rate_tier' => $tier,
                'valid_from' => $from->toDateString(),
                'valid_to' => $to->toDateString(),
                'priority' => $priority,
                'with_fuel_rate_pkr' => $withPkr,
                'without_fuel_rate_pkr' => $withoutPkr,
                'with_fuel_rate_usd' => $withUsd,
                'without_fuel_rate_usd' => $withoutUsd,
                'daily_mileage_allowance_km' => filled($row['daily_mileage_allowance_km'] ?? null) ? max(0, (int) $row['daily_mileage_allowance_km']) : null,
                'excess_km_rate_pkr' => $this->normalizePositiveDecimal($row['excess_km_rate_pkr'] ?? null, 'excess_km_rate_pkr'),
                'excess_km_rate_usd' => $this->normalizePositiveDecimal($row['excess_km_rate_usd'] ?? null, 'excess_km_rate_usd'),
                'excess_km_terms' => $this->sanitizer->sanitizePlainText($row['excess_km_terms'] ?? null, 'rate.excess_km_terms', 255) ?: null,
                'is_active' => $isActive,
            ];
        }

        return $sanitized;
    }

    /**
     * @param  array<int|string, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function validateRouteAdjustments(array $rows): array
    {
        $sanitized = [];

        foreach (array_values($rows) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $mode = (string) ($row['adjustment_mode'] ?? VehicleRouteAdjustment::MODE_FIXED_PER_TRIP);
            if (! in_array($mode, [VehicleRouteAdjustment::MODE_FIXED_PER_TRIP, VehicleRouteAdjustment::MODE_PER_DAY], true)) {
                throw new InvalidArgumentException("Invalid route adjustment mode [{$mode}].");
            }

            $pkr = $this->normalizePositiveDecimal($row['amount_pkr'] ?? null, 'route_adjustment.amount_pkr');
            $usd = $this->normalizePositiveDecimal($row['amount_usd'] ?? null, 'route_adjustment.amount_usd');

            if ($pkr === null && $usd === null) {
                throw new InvalidArgumentException('Route adjustment must specify a positive PKR or USD amount.');
            }

            $sanitized[] = [
                'region_id' => filled($row['region_id'] ?? null) ? (int) $row['region_id'] : null,
                'destination_id' => filled($row['destination_id'] ?? null) ? (int) $row['destination_id'] : null,
                'route_label' => $this->sanitizer->sanitizePlainText($row['route_label'] ?? 'Route Fuel Adjustment', 'route_adjustment.label', 140),
                'adjustment_mode' => $mode,
                'amount_pkr' => $pkr,
                'amount_usd' => $usd,
                'valid_from' => filled($row['valid_from'] ?? null) ? CarbonImmutable::parse((string) $row['valid_from'])->toDateString() : null,
                'valid_to' => filled($row['valid_to'] ?? null) ? CarbonImmutable::parse((string) $row['valid_to'])->toDateString() : null,
                'is_active' => (bool) ($row['is_active'] ?? true),
                'notes' => $this->sanitizer->sanitizePlainText($row['notes'] ?? null, 'route_adjustment.notes', 255) ?: null,
            ];
        }

        return $sanitized;
    }

    /**
     * @param  array<int|string, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function validateDriverAssignments(array $rows): array
    {
        $sanitized = [];

        foreach (array_values($rows) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $driverName = $this->sanitizer->sanitizePlainText($row['driver_name'] ?? null, 'driver.name', 140);
            if ($driverName === '') {
                continue;
            }

            $sanitized[] = [
                'driver_name' => $driverName,
                'driver_phone' => $this->sanitizer->sanitizePlainText($row['driver_phone'] ?? null, 'driver.phone', 40) ?: null,
                'license_number' => $this->sanitizer->sanitizePlainText($row['license_number'] ?? null, 'driver.license', 64) ?: null,
                'assigned_from' => CarbonImmutable::parse((string) ($row['assigned_from'] ?? now()->toDateString()))->toDateString(),
                'assigned_to' => filled($row['assigned_to'] ?? null) ? CarbonImmutable::parse((string) $row['assigned_to'])->toDateString() : null,
                'is_primary' => (bool) ($row['is_primary'] ?? true),
                'notes' => $this->sanitizer->sanitizePlainText($row['notes'] ?? null, 'driver.notes', 255) ?: null,
            ];
        }

        return $sanitized;
    }

    /**
     * @param  array<int|string, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function validateBlackouts(array $rows): array
    {
        $sanitized = [];

        foreach (array_values($rows) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $start = CarbonImmutable::parse((string) ($row['start_date'] ?? ''));
            $end = CarbonImmutable::parse((string) ($row['end_date'] ?? ''));
            if ($end->lt($start)) {
                throw new InvalidArgumentException('Blackout end date cannot precede start date.');
            }

            $sanitized[] = [
                'reason' => $this->sanitizer->sanitizePlainText($row['reason'] ?? 'maintenance', 'blackout.reason', 64),
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'notes' => $this->sanitizer->sanitizePlainText($row['notes'] ?? null, 'blackout.notes', 255) ?: null,
            ];
        }

        return $sanitized;
    }

    private function normalizePositiveDecimal(mixed $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Field [{$field}] must be numeric.");
        }

        $formatted = number_format((float) $value, 2, '.', '');
        if (bccomp($formatted, '0.00', 2) <= 0) {
            throw new InvalidArgumentException("Field [{$field}] must be greater than zero.");
        }

        return $formatted;
    }
}
