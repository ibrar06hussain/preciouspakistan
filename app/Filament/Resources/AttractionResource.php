<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttractionResource\Pages;
use App\Modules\Destinations\Models\Attraction;
use App\Modules\Destinations\Models\Destination;
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

class AttractionResource extends Resource
{
    protected static ?string $model = Attraction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|UnitEnum|null $navigationGroup = 'Destinations';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Attraction Details')
                ->columns(2)
                ->schema([
                    Select::make('destination_id')
                        ->label('Parent Destination')
                        ->options(fn (): array => Destination::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->required()
                        ->searchable(),
                    Select::make('category')
                        ->options(Attraction::CATEGORIES)
                        ->default('landmark')
                        ->required(),
                    TextInput::make('name')->required()->maxLength(160),
                    TextInput::make('slug')->required()->maxLength(191)->unique(ignoreRecord: true),
                    TextInput::make('latitude')->numeric()->minValue(-90)->maxValue(90),
                    TextInput::make('longitude')->numeric()->minValue(-180)->maxValue(180),
                    Toggle::make('is_active')->default(true),
                    Textarea::make('summary')->rows(3)->columnSpanFull(),
                    Textarea::make('description_html')->label('Rich Description (Sanitized HTML)')->rows(4)->columnSpanFull(),
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
                TextColumn::make('destination.name')->label('Destination')->badge()->color('primary')->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn (string $state): string => Attraction::CATEGORIES[$state] ?? ucfirst($state)),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('destination_id')
                    ->label('Destination')
                    ->relationship('destination', 'name')
                    ->preload(),
                SelectFilter::make('category')
                    ->options(Attraction::CATEGORIES),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttractions::route('/'),
            'create' => Pages\CreateAttraction::route('/create'),
            'edit' => Pages\EditAttraction::route('/{record}/edit'),
        ];
    }
}
