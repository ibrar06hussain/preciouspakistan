<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NomadLocationResource\Pages;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Nomads\Models\NomadFacility;
use App\Modules\Nomads\Models\NomadLocation;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class NomadLocationResource extends Resource
{
    protected static ?string $model = NomadLocation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wifi';

    protected static string|UnitEnum|null $navigationGroup = 'Digital Nomads';

    protected static ?string $navigationLabel = 'Nomad Workspaces';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Workspace Location & Honest Infrastructure Disclosures')
                ->description('Standalone checkout is disabled by policy; workspaces attach as tour add-ons only.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(160),
                    TextInput::make('slug')->required()->maxLength(191)->unique(ignoreRecord: true),
                    Select::make('destination_id')
                        ->label('Tour Destination')
                        ->options(fn () => Destination::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->required()
                        ->searchable(),
                    Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive'])->default('active')->required(),
                    TextInput::make('power_source')->maxLength(140)->placeholder('WAPDA / Local Hydro Grid'),
                    TextInput::make('backup_power_details')->maxLength(255)->placeholder('10kW Solar Hybrid + Lithium Inverter + Standby Generator'),
                    TextInput::make('internet_provider')->maxLength(120)->placeholder('SCO Fiber + Starlink Secondary'),
                    TextInput::make('operating_hours')->maxLength(160)->placeholder('08:00–22:00 PKT'),
                    TextInput::make('advertised_download_mbps')->numeric()->minValue(1),
                    TextInput::make('advertised_upload_mbps')->numeric()->minValue(1),
                    Toggle::make('power_guaranteed')->label('Power SLA Guaranteed?'),
                    Toggle::make('internet_guaranteed')->label('Internet SLA Guaranteed?'),
                    Textarea::make('connectivity_warning')->label('Explicit Honesty / Reliability Notice')->rows(2)->columnSpanFull(),
                ]),

            Section::make('Desk Seats, Offices, Meeting Rooms & Seasonal Rates')
                ->schema([
                    Repeater::make('facilities')
                        ->collapsible()
                        ->schema([
                            TextInput::make('name')->required()->maxLength(140),
                            TextInput::make('code')->required()->maxLength(48),
                            Select::make('facility_type')->options(NomadFacility::TYPES)->required(),
                            TextInput::make('seat_capacity')->numeric()->required()->minValue(1)->default(10),
                            Toggle::make('allows_daily')->default(true),
                            Toggle::make('allows_hourly')->default(true),
                            Repeater::make('rates')
                                ->schema([
                                    TextInput::make('rate_name')->required(),
                                    DatePicker::make('valid_from')->required(),
                                    DatePicker::make('valid_to')->required(),
                                    TextInput::make('daily_rate_pkr')->numeric()->minValue(1),
                                    TextInput::make('daily_rate_usd')->numeric()->minValue(1),
                                    TextInput::make('hourly_rate_pkr')->numeric()->minValue(1),
                                    TextInput::make('hourly_rate_usd')->numeric()->minValue(1),
                                ])
                                ->columns(4)
                                ->columnSpanFull(),
                        ])
                        ->columns(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('destination.name')->badge()->color('primary'),
                TextColumn::make('internet_provider')->label('ISP')->placeholder('—'),
                IconColumn::make('power_guaranteed')->label('Power SLA')->boolean(),
                IconColumn::make('internet_guaranteed')->label('Net SLA')->boolean(),
                TextColumn::make('facilities_count')->label('Facilities')->counts('facilities')->badge(),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNomadLocations::route('/'),
            'create' => Pages\CreateNomadLocation::route('/create'),
            'edit' => Pages\EditNomadLocation::route('/{record}/edit'),
        ];
    }
}
