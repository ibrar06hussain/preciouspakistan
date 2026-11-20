<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Services\FleetCatalogService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('copy_rates_next_year')
                ->label('Copy Rates to Next Year')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->visible(fn (): bool => auth()->user()?->can('vehicles.rates.manage') ?? false)
                ->form([
                    TextInput::make('source_year')->numeric()->required()->default(now()->year),
                    TextInput::make('target_year')->numeric()->required()->default(now()->year + 1),
                ])
                ->action(function (array $data): void {
                    try {
                        /** @var User $actor */
                        $actor = auth()->user();
                        /** @var Vehicle $vehicle */
                        $vehicle = $this->getRecord();
                        $copied = app(FleetCatalogService::class)->copyRatesToYear(
                            $actor,
                            $vehicle,
                            (int) $data['source_year'],
                            (int) $data['target_year']
                        );
                        Notification::make()->title("Copied {$copied} vehicle rate period(s).")->success()->send();
                    } catch (Throwable $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Vehicle $vehicle */
        $vehicle = $this->getRecord();
        $vehicle->load(['seasonalRates', 'routeAdjustments', 'driverAssignments', 'blackouts']);

        $data['driver_assignments'] = $vehicle->driverAssignments->map(fn ($d): array => [
            'driver_name' => $d->driver_name,
            'driver_phone' => $d->driver_phone,
            'license_number' => $d->license_number,
            'assigned_from' => $d->assigned_from?->toDateString(),
            'assigned_to' => $d->assigned_to?->toDateString(),
            'is_primary' => (bool) $d->is_primary,
        ])->all();

        $data['seasonal_rates'] = $vehicle->seasonalRates->map(fn ($r): array => [
            'rate_name' => $r->rate_name,
            'rate_tier' => $r->rate_tier,
            'valid_from' => $r->valid_from?->toDateString(),
            'valid_to' => $r->valid_to?->toDateString(),
            'priority' => $r->priority,
            'with_fuel_rate_pkr' => $r->with_fuel_rate_pkr,
            'without_fuel_rate_pkr' => $r->without_fuel_rate_pkr,
            'with_fuel_rate_usd' => $r->with_fuel_rate_usd,
            'without_fuel_rate_usd' => $r->without_fuel_rate_usd,
            'daily_mileage_allowance_km' => $r->daily_mileage_allowance_km,
            'excess_km_rate_pkr' => $r->excess_km_rate_pkr,
            'excess_km_terms' => $r->excess_km_terms,
            'is_active' => (bool) $r->is_active,
        ])->all();

        $data['route_adjustments'] = $vehicle->routeAdjustments->map(fn ($a): array => [
            'route_label' => $a->route_label,
            'region_id' => $a->region_id,
            'destination_id' => $a->destination_id,
            'adjustment_mode' => $a->adjustment_mode,
            'amount_pkr' => $a->amount_pkr,
            'amount_usd' => $a->amount_usd,
            'is_active' => (bool) $a->is_active,
        ])->all();

        $data['blackouts'] = $vehicle->blackouts->map(fn ($b): array => [
            'reason' => $b->reason,
            'start_date' => $b->start_date?->toDateString(),
            'end_date' => $b->end_date?->toDateString(),
            'notes' => $b->notes,
        ])->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();
        /** @var Vehicle $vehicle */
        $vehicle = $record;

        if (! $actor->can('vehicles.rates.manage')) {
            unset($data['seasonal_rates'], $data['route_adjustments']);
        }

        return app(FleetCatalogService::class)->saveVehicle($actor, $vehicle, $data);
    }
}
