<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\JoditEditor;
use App\Filament\Resources\VehicleResource\Pages;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\Region;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleRouteAdjustment;
use App\Modules\Fleet\Models\VehicleSeasonalRate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|UnitEnum|null $navigationGroup = 'Company Fleet';

    protected static ?string $navigationLabel = 'Vehicles & Rates';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Company Vehicle Profile')
                ->description('Driver is ALWAYS included in every rental rate — never billed separately.')
                ->columns(3)
                ->schema([
                    TextInput::make('code')->required()->maxLength(40)->unique(ignoreRecord: true)->placeholder('FLT-PRADO-01'),
                    TextInput::make('name')->required()->maxLength(160)->placeholder('Toyota Land Cruiser Prado 4x4'),
                    TextInput::make('slug')->required()->maxLength(191)->unique(ignoreRecord: true),
                    Select::make('vehicle_type')->options(Vehicle::TYPES)->required(),
                    TextInput::make('make')->required()->maxLength(80),
                    TextInput::make('model')->required()->maxLength(80),
                    TextInput::make('model_year')->numeric()->minValue(1995)->maxValue(2035),
                    TextInput::make('passenger_capacity')->numeric()->required()->minValue(1)->maxValue(60)->default(4),
                    TextInput::make('luggage_capacity')->numeric()->required()->minValue(0)->maxValue(60)->default(4),
                    Select::make('transmission')->options(['automatic' => 'Automatic', 'manual' => 'Manual'])->default('automatic'),
                    Select::make('status')->options([
                        Vehicle::STATUS_ACTIVE => 'Active',
                        Vehicle::STATUS_MAINTENANCE => 'Maintenance',
                        Vehicle::STATUS_RETIRED => 'Retired',
                    ])->default(Vehicle::STATUS_ACTIVE)->required(),
                    Toggle::make('has_ac')->label('A/C Equipped')->default(true),
                    TagsInput::make('amenities')->columnSpanFull(),
                    CheckboxList::make('routing_suitability')
                        ->options(Vehicle::ROUTE_TAGS)
                        ->columns(2)
                        ->columnSpanFull(),
                    Textarea::make('summary')->rows(2)->columnSpanFull(),
                    JoditEditor::make('description_html')->label('Public Vehicle Description')->minHeight(180)->columnSpanFull(),
                ]),

            Section::make('Private Fleet Identity & Assigned Drivers (Admin Only)')
                ->description('Registration plates, internal tags, and driver contact details are strictly hidden from public pages.')
                ->columns(3)
                ->schema([
                    TextInput::make('registration_number')->label('Registration Number (Private)')->maxLength(64)->unique(ignoreRecord: true),
                    TextInput::make('internal_fleet_tag')->label('Internal Fleet Tag')->maxLength(64),
                    Toggle::make('is_sample_content')->label('Sample Fleet Record')->default(true),
                    Textarea::make('private_notes')->rows(2)->columnSpanFull(),
                    Repeater::make('driver_assignments')
                        ->label('Assigned Company Drivers (Included in Rate)')
                        ->schema([
                            TextInput::make('driver_name')->required()->maxLength(140),
                            TextInput::make('driver_phone')->tel()->maxLength(40),
                            TextInput::make('license_number')->maxLength(64),
                            DatePicker::make('assigned_from')->required(),
                            DatePicker::make('assigned_to'),
                            Toggle::make('is_primary')->default(true),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Seasonal / Yearly With-Fuel & Without-Fuel Daily Rates')
                ->description('Trips crossing seasons or years are prorated by chargeable date. Equal-priority overlaps are rejected.')
                ->visible(fn (): bool => auth()->user()?->can('vehicles.rates.manage') ?? false)
                ->schema([
                    Repeater::make('seasonal_rates')
                        ->label('Rate Periods')
                        ->collapsible()
                        ->schema([
                            TextInput::make('rate_name')->required()->maxLength(120),
                            Select::make('rate_tier')->options([
                                VehicleSeasonalRate::TIER_DEFAULT => 'Default',
                                VehicleSeasonalRate::TIER_STANDARD_YEARLY => 'Standard Yearly',
                                VehicleSeasonalRate::TIER_SEASONAL_YEARLY => 'Seasonal Yearly',
                                VehicleSeasonalRate::TIER_SPECIAL => 'Special Override',
                            ])->default(VehicleSeasonalRate::TIER_STANDARD_YEARLY)->required(),
                            DatePicker::make('valid_from')->required(),
                            DatePicker::make('valid_to')->required(),
                            TextInput::make('priority')->numeric()->default(10)->minValue(1),
                            TextInput::make('with_fuel_rate_pkr')->label('With Fuel (PKR/day)')->numeric()->minValue(1),
                            TextInput::make('without_fuel_rate_pkr')->label('Without Fuel (PKR/day)')->numeric()->minValue(1),
                            TextInput::make('with_fuel_rate_usd')->label('With Fuel (USD/day)')->numeric()->minValue(1),
                            TextInput::make('without_fuel_rate_usd')->label('Without Fuel (USD/day)')->numeric()->minValue(1),
                            TextInput::make('daily_mileage_allowance_km')->label('Daily Mileage (km)')->numeric()->minValue(0),
                            TextInput::make('excess_km_rate_pkr')->label('Excess km (PKR)')->numeric()->minValue(1),
                            TextInput::make('excess_km_terms')->maxLength(255),
                            Toggle::make('is_active')->default(true),
                        ])
                        ->columns(4),
                ]),

            Section::make('Route-Specific Fuel Adjustments (Applied to With-Fuel Only)')
                ->description('Destination-specific rules override region-level rules to prevent double-charging fuel.')
                ->collapsed()
                ->visible(fn (): bool => auth()->user()?->can('vehicles.rates.manage') ?? false)
                ->schema([
                    Repeater::make('route_adjustments')
                        ->schema([
                            TextInput::make('route_label')->required()->maxLength(140),
                            Select::make('region_id')->label('Region')->options(fn () => Region::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->nullable(),
                            Select::make('destination_id')->label('Destination')->options(fn () => Destination::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->nullable(),
                            Select::make('adjustment_mode')->options([
                                VehicleRouteAdjustment::MODE_FIXED_PER_TRIP => 'Fixed Per Trip',
                                VehicleRouteAdjustment::MODE_PER_DAY => 'Per Rental Day',
                            ])->required(),
                            TextInput::make('amount_pkr')->label('Adjustment (PKR)')->numeric()->minValue(1),
                            TextInput::make('amount_usd')->label('Adjustment (USD)')->numeric()->minValue(1),
                            Toggle::make('is_active')->default(true),
                        ])
                        ->columns(4),
                ]),

            Section::make('Maintenance Blackouts')
                ->collapsed()
                ->schema([
                    Repeater::make('blackouts')
                        ->schema([
                            Select::make('reason')->options([
                                'maintenance' => 'Scheduled Maintenance',
                                'inspection' => 'Safety Inspection',
                                'private_hold' => 'Internal Hold',
                            ])->default('maintenance')->required(),
                            DatePicker::make('start_date')->required(),
                            DatePicker::make('end_date')->required(),
                            TextInput::make('notes')->maxLength(255),
                        ])
                        ->columns(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->color('gray')->searchable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('vehicle_type')->badge()->color('primary')->formatStateUsing(fn (string $state): string => Vehicle::TYPES[$state] ?? $state),
                TextColumn::make('passenger_capacity')->label('Seats')->sortable(),
                TextColumn::make('registration_number')->label('Reg (Private)')->searchable()->toggleable(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    Vehicle::STATUS_ACTIVE => 'success',
                    Vehicle::STATUS_MAINTENANCE => 'warning',
                    default => 'danger',
                }),
                IconColumn::make('driver_always_included')->label('Driver Incl.')->boolean(),
            ])
            ->filters([
                SelectFilter::make('vehicle_type')->options(Vehicle::TYPES),
                SelectFilter::make('status')->options([
                    Vehicle::STATUS_ACTIVE => 'Active',
                    Vehicle::STATUS_MAINTENANCE => 'Maintenance',
                    Vehicle::STATUS_RETIRED => 'Retired',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
