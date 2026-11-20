<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleReservationResource\Pages;
use App\Models\User;
use App\Modules\Fleet\Models\VehicleReservation;
use App\Modules\Fleet\Services\VehicleAvailabilityService;
use App\Shared\Support\DisplayTimezone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class VehicleReservationResource extends Resource
{
    protected static ?string $model = VehicleReservation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Company Fleet';

    protected static ?string $navigationLabel = 'Fleet Assignments';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_code')->badge()->color('gray')->searchable(),
                TextColumn::make('vehicle.name')->label('Vehicle')->searchable(),
                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->sortable(),
                TextColumn::make('fuel_mode')->badge()->color(fn (string $state): string => $state === VehicleReservation::FUEL_WITH ? 'primary' : 'info'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    VehicleReservation::STATUS_CONFIRMED => 'success',
                    VehicleReservation::STATUS_HOLD => 'warning',
                    default => 'gray',
                }),
                TextColumn::make('expires_at')->label('Hold Expires (PKT)')->formatStateUsing(fn ($state): string => DisplayTimezone::format($state)),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    VehicleReservation::STATUS_HOLD => 'Hold',
                    VehicleReservation::STATUS_CONFIRMED => 'Confirmed',
                    VehicleReservation::STATUS_RELEASED => 'Released',
                    VehicleReservation::STATUS_CANCELLED => 'Cancelled',
                ]),
            ])
            ->recordActions([
                Action::make('release')
                    ->label('Release Hold')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (VehicleReservation $record): bool => in_array($record->status, [VehicleReservation::STATUS_HOLD, VehicleReservation::STATUS_CONFIRMED], true)
                        && (auth()->user()?->can('vehicles.manage') ?? false))
                    ->action(function (VehicleReservation $record): void {
                        /** @var User $actor */
                        $actor = auth()->user();
                        app(VehicleAvailabilityService::class)->releaseReservation($record, $actor);
                        Notification::make()->title('Vehicle assignment released.')->success()->send();
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicleReservations::route('/'),
        ];
    }
}
