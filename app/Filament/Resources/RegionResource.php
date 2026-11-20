<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegionResource\Pages;
use App\Modules\Destinations\Models\Country;
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
use Filament\Tables\Table;
use UnitEnum;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-asia-australia';

    protected static string|UnitEnum|null $navigationGroup = 'Destinations';

    protected static ?string $navigationLabel = 'Regions & Provinces';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Region Hierarchy & Map')
                ->columns(2)
                ->schema([
                    Select::make('country_id')
                        ->label('Country')
                        ->options(fn (): array => Country::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->default(fn () => Country::query()->where('iso_code', 'PK')->value('id'))
                        ->required(),
                    Select::make('region_type')
                        ->options([
                            'province' => 'Province',
                            'autonomous_territory' => 'Autonomous Territory',
                            'federal_territory' => 'Federal Territory',
                        ])
                        ->required(),
                    TextInput::make('name')->required()->maxLength(140),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(160)
                        ->unique(ignoreRecord: true)
                        ->helperText('Changing a slug automatically issues a 301 redirect from the previous URL.'),
                    TextInput::make('tagline')->maxLength(220)->columnSpanFull(),
                    Textarea::make('summary')->rows(3)->columnSpanFull(),
                    Textarea::make('description_html')->label('Rich Description (Sanitized HTML)')->rows(4)->columnSpanFull(),
                    TextInput::make('latitude')->numeric()->minValue(-90)->maxValue(90),
                    TextInput::make('longitude')->numeric()->minValue(-180)->maxValue(180),
                    TextInput::make('default_zoom')->numeric()->default(7)->minValue(4)->maxValue(16),
                    TextInput::make('sort_order')->numeric()->default(0),
                    Toggle::make('is_active')->default(true),
                ]),

            Section::make('SEO Metadata')
                ->columns(2)
                ->schema([
                    TextInput::make('seo_title')->maxLength(180),
                    TextInput::make('canonical_url')->maxLength(255),
                    Textarea::make('seo_description')->rows(2)->maxLength(320)->columnSpanFull(),
                    Toggle::make('noindex')->label('Noindex'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->badge()->color('gray')->searchable(),
                TextColumn::make('region_type')->badge()->color('primary'),
                TextColumn::make('destinations_count')->label('Destinations')->counts('destinations')->badge()->color('success'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegions::route('/'),
            'create' => Pages\CreateRegion::route('/create'),
            'edit' => Pages\EditRegion::route('/{record}/edit'),
        ];
    }
}
