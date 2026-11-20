<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DistrictResource\Pages;
use App\Modules\Destinations\Models\District;
use App\Modules\Destinations\Models\Region;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
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

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|UnitEnum|null $navigationGroup = 'Destinations';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('District Details')
                ->columns(2)
                ->schema([
                    Select::make('region_id')
                        ->label('Parent Region')
                        ->options(fn (): array => Region::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                        ->required()
                        ->searchable(),
                    TextInput::make('name')->required()->maxLength(140),
                    TextInput::make('slug')->required()->maxLength(160)->unique(ignoreRecord: true),
                    Toggle::make('is_active')->default(true),
                    TextInput::make('latitude')->numeric()->minValue(-90)->maxValue(90),
                    TextInput::make('longitude')->numeric()->minValue(-180)->maxValue(180),
                    Textarea::make('summary')->rows(3)->columnSpanFull(),
                    TextInput::make('seo_title')->maxLength(180),
                    TextInput::make('seo_description')->maxLength(320),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('region.name')->label('Region')->badge()->color('primary')->sortable(),
                TextColumn::make('destinations_count')->label('Destinations')->counts('destinations')->badge(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('region_id')
                    ->label('Region')
                    ->relationship('region', 'name')
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistricts::route('/'),
            'create' => Pages\CreateDistrict::route('/create'),
            'edit' => Pages\EditDistrict::route('/{record}/edit'),
        ];
    }
}
