<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\JoditEditor;
use App\Filament\Resources\HotelResource\Pages;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Hotels\Models\Hotel;
use App\Modules\Hotels\Models\HotelRoomRate;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class HotelResource extends Resource
{
    protected static ?string $model = Hotel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Tour-Only Hotels';

    protected static ?string $navigationLabel = 'Tour Hotels & Rates';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tour-Only Hotel Profile')
                ->description('Company-managed accommodation suggestions offered exclusively inside tours. No standalone booking or hotel-partner login exists.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(180),
                    TextInput::make('slug')->required()->maxLength(191)->unique(ignoreRecord: true),
                    Select::make('destination_id')
                        ->label('Destination')
                        ->options(fn (): array => Destination::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->required()
                        ->searchable(),
                    Select::make('status')
                        ->options([
                            Hotel::STATUS_ACTIVE => 'Active',
                            Hotel::STATUS_DRAFT => 'Draft',
                            Hotel::STATUS_INACTIVE => 'Inactive',
                        ])
                        ->default(Hotel::STATUS_ACTIVE)
                        ->required(),
                    TextInput::make('address')->maxLength(255)->columnSpanFull(),
                    TextInput::make('latitude')->numeric()->minValue(-90)->maxValue(90),
                    TextInput::make('longitude')->numeric()->minValue(-180)->maxValue(180),
                    TextInput::make('public_contact_phone')->label('Optional Public Phone')->maxLength(40),
                    TextInput::make('public_contact_email')->label('Optional Public Email')->email()->maxLength(191),
                    TextInput::make('main_photo_url')->maxLength(255),
                    Toggle::make('is_sample_content')->label('Sample / Demo Listing')->default(true),
                    JoditEditor::make('short_description_html')
                        ->label('Short Rich Description (Jodit WYSIWYG)')
                        ->minHeight(180)
                        ->columnSpanFull(),
                ]),

            Section::make('Room Types & Annual/Seasonal Nightly Rates')
                ->description('Define max occupancy and PKR/USD nightly rates per date range. Precedence: special > seasonal_yearly > standard_yearly > default.')
                ->schema([
                    Repeater::make('room_types')
                        ->label('Room Types')
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => ($state['name'] ?? 'Room Type').' (Max '.($state['max_occupancy'] ?? 2).')')
                        ->schema([
                            TextInput::make('name')->required()->maxLength(120)->placeholder('Deluxe Mountain View'),
                            TextInput::make('code')->required()->maxLength(48)->placeholder('DLX_MTN'),
                            TextInput::make('max_occupancy')->numeric()->required()->minValue(1)->maxValue(20)->default(2),
                            TextInput::make('bed_configuration')->maxLength(120)->placeholder('1 King or 2 Twin'),
                            Toggle::make('is_active')->default(true),
                            Repeater::make('rates')
                                ->label('Seasonal / Yearly Nightly Rates')
                                ->schema([
                                    TextInput::make('rate_name')->required()->maxLength(120)->placeholder('2027 Peak Summer'),
                                    Select::make('rate_tier')->options([
                                        HotelRoomRate::TIER_DEFAULT => 'Default',
                                        HotelRoomRate::TIER_STANDARD_YEARLY => 'Standard Yearly',
                                        HotelRoomRate::TIER_SEASONAL_YEARLY => 'Seasonal Yearly',
                                        HotelRoomRate::TIER_SPECIAL => 'Special Override',
                                    ])->default(HotelRoomRate::TIER_STANDARD_YEARLY)->required(),
                                    DatePicker::make('valid_from')->required(),
                                    DatePicker::make('valid_to')->required(),
                                    TextInput::make('priority')->numeric()->default(10)->minValue(1),
                                    TextInput::make('nightly_rate_pkr')->label('Nightly PKR')->numeric()->minValue(1),
                                    TextInput::make('nightly_rate_usd')->label('Nightly USD')->numeric()->minValue(1),
                                    Toggle::make('is_active')->default(true),
                                ])
                                ->columns(4)
                                ->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ]),

            Section::make('SEO Metadata')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('seo_title')->maxLength(180),
                    TextInput::make('canonical_url')->maxLength(255),
                    Textarea::make('seo_description')->rows(2)->maxLength(320)->columnSpanFull(),
                    Toggle::make('noindex'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('destination.name')->label('Destination')->badge()->color('primary')->sortable(),
                TextColumn::make('region.name')->label('Region')->sortable(),
                TextColumn::make('room_types_count')->label('Room Types')->counts('roomTypes')->badge()->color('info'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    Hotel::STATUS_ACTIVE => 'success',
                    Hotel::STATUS_DRAFT => 'warning',
                    default => 'gray',
                }),
            ])
            ->filters([
                SelectFilter::make('destination_id')->label('Destination')->relationship('destination', 'name')->preload(),
                SelectFilter::make('status')->options([
                    Hotel::STATUS_ACTIVE => 'Active',
                    Hotel::STATUS_DRAFT => 'Draft',
                    Hotel::STATUS_INACTIVE => 'Inactive',
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
            'index' => Pages\ListHotels::route('/'),
            'create' => Pages\CreateHotel::route('/create'),
            'edit' => Pages\EditHotel::route('/{record}/edit'),
        ];
    }
}
