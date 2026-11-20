<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DestinationResource\Pages;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\District;
use App\Modules\Destinations\Models\Region;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class DestinationResource extends Resource
{
    protected static ?string $model = Destination::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|UnitEnum|null $navigationGroup = 'Destinations';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Destination Hierarchy & Location')
                ->columns(2)
                ->schema([
                    Select::make('region_id')
                        ->label('Region / Province / Territory')
                        ->options(fn (): array => Region::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                        ->required()
                        ->live()
                        ->searchable(),
                    Select::make('district_id')
                        ->label('District (Scoped to Selected Region)')
                        ->options(fn (Get $get): array => $get('region_id')
                            ? District::query()->where('region_id', $get('region_id'))->orderBy('name')->pluck('name', 'id')->all()
                            : [])
                        ->searchable()
                        ->nullable(),
                    TextInput::make('name')->required()->maxLength(160),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(191)
                        ->unique(ignoreRecord: true)
                        ->helperText('Changing a slug automatically issues a 301 redirect from /destinations/{old-slug}.'),
                    TextInput::make('tagline')->maxLength(220),
                    TextInput::make('best_season')->maxLength(120)->placeholder('e.g. Apr–Oct'),
                    TextInput::make('elevation_meters')->numeric()->minValue(0)->maxValue(9000),
                    TextInput::make('hero_image_url')->maxLength(255),
                    TextInput::make('latitude')->numeric()->minValue(-90)->maxValue(90),
                    TextInput::make('longitude')->numeric()->minValue(-180)->maxValue(180),
                    Toggle::make('is_featured')->label('Featured'),
                    Toggle::make('is_active')->default(true),
                    Textarea::make('summary')->rows(3)->columnSpanFull(),
                    Textarea::make('description_html')->label('Rich Description (Sanitized HTML)')->rows(5)->columnSpanFull(),
                ]),

            Section::make('Gallery')
                ->collapsed()
                ->schema([
                    Repeater::make('gallery')
                        ->schema([
                            TextInput::make('url')->required()->maxLength(255),
                            TextInput::make('alt')->required()->maxLength(180),
                            TextInput::make('caption')->maxLength(220),
                        ])
                        ->columns(3),
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
                TextColumn::make('region.name')->label('Region')->badge()->color('primary')->sortable(),
                TextColumn::make('district.name')->label('District')->placeholder('—')->sortable(),
                TextColumn::make('best_season')->label('Season')->placeholder('—'),
                TextColumn::make('attractions_count')->label('Attractions')->counts('attractions')->badge()->color('warning'),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('region_id')
                    ->label('Region')
                    ->relationship('region', 'name')
                    ->preload(),
                TernaryFilter::make('is_featured')->label('Featured'),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDestinations::route('/'),
            'create' => Pages\CreateDestination::route('/create'),
            'edit' => Pages\EditDestination::route('/{record}/edit'),
        ];
    }
}
