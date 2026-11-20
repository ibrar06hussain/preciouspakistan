<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\JoditEditor;
use App\Filament\Resources\TourResource\Pages;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Models\TourDeparture;
use BackedEnum;
use Filament\Actions\EditAction;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class TourResource extends Resource
{
    protected static ?string $model = Tour::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|UnitEnum|null $navigationGroup = 'Tours & Packages';

    protected static ?string $navigationLabel = 'Tour Packages';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Core Tour Package Details')
                ->columns(2)
                ->schema([
                    TextInput::make('code')->required()->maxLength(40)->unique(ignoreRecord: true)->placeholder('PP-HUNZA-07'),
                    TextInput::make('title')->required()->maxLength(191),
                    TextInput::make('slug')->required()->maxLength(191)->unique(ignoreRecord: true),
                    Select::make('category')->options(Tour::CATEGORIES)->required(),
                    Select::make('difficulty')->options(Tour::DIFFICULTIES)->default('moderate')->required(),
                    TagsInput::make('languages')->default(['English', 'Urdu']),
                    TextInput::make('duration_days')->numeric()->required()->minValue(1)->maxValue(60)->default(5),
                    TextInput::make('duration_nights')->numeric()->required()->minValue(0)->maxValue(60)->default(4),
                    TextInput::make('min_guests')->numeric()->required()->minValue(1)->default(1),
                    TextInput::make('max_guests')->numeric()->required()->minValue(1)->maxValue(100)->default(12),
                    TextInput::make('booking_lead_days')->numeric()->minValue(0)->default(2),
                    TextInput::make('booking_window_days')->numeric()->minValue(1)->default(365),
                    TextInput::make('pickup_location')->maxLength(180)->placeholder('Islamabad International Airport (ISB)'),
                    TextInput::make('dropoff_location')->maxLength(180)->placeholder('Islamabad'),
                    Select::make('destination_ids')
                        ->label('Attached Pakistan Destinations')
                        ->multiple()
                        ->options(fn (): array => Destination::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),
                    Textarea::make('summary')->rows(3)->required()->columnSpanFull(),
                    JoditEditor::make('description_html')
                        ->label('Full Tour Description (Jodit WYSIWYG)')
                        ->columnSpanFull(),
                ]),

            Section::make('Pricing Model & Service Options')
                ->description('Unknown or custom prices always render as "Request Quote" publicly — never $0.')
                ->columns(3)
                ->schema([
                    Select::make('pricing_model')
                        ->options(Tour::PRICING_MODELS)
                        ->default(Tour::PRICING_CUSTOM_QUOTE)
                        ->required(),
                    TextInput::make('base_price_pkr')->label('Base Price (PKR)')->numeric()->minValue(1),
                    TextInput::make('base_price_usd')->label('Base Price (USD)')->numeric()->minValue(1),
                    Toggle::make('includes_base_accommodation')->label('Base Price Includes Lodging'),
                    Toggle::make('requires_company_vehicle')->label('Requires Company Vehicle')->default(true),
                    Toggle::make('supports_hotel_selection')->label('Allow Tour-Only Hotel Choice')->default(true),
                    Toggle::make('supports_nomad_addon')->label('Digital Nomad Add-On Eligible'),
                    Toggle::make('is_featured')->label('Featured Tour'),
                    Toggle::make('is_sample_content')->label('Sample / Demo Content')->default(true),
                ]),

            Section::make('Day-by-Day Chronological Itinerary')
                ->description('Must run sequentially from Day 1 through duration_days with no gaps before publishing.')
                ->schema([
                    Repeater::make('itinerary_days')
                        ->label('Itinerary Days')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => 'Day '.($state['day_number'] ?? '?').': '.($state['title'] ?? 'Untitled'))
                        ->schema([
                            TextInput::make('day_number')->numeric()->required()->minValue(1),
                            TextInput::make('title')->required()->maxLength(191),
                            Select::make('destination_id')
                                ->label('Primary Stop Destination')
                                ->options(fn (): array => Destination::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->nullable(),
                            TextInput::make('meals_included')->maxLength(120)->placeholder('Breakfast, Dinner'),
                            TextInput::make('altitude_meters')->numeric()->minValue(0),
                            TextInput::make('travel_distance_km')->numeric()->minValue(0),
                            JoditEditor::make('description_html')
                                ->label('Day Details (Jodit WYSIWYG)')
                                ->minHeight(180)
                                ->columnSpanFull(),
                        ])
                        ->columns(3),
                ]),

            Section::make('Overnight Tour Stays')
                ->description('Attach overnight stay destinations here; eligible tour-only hotels link to these stays in Phase 6.')
                ->schema([
                    Repeater::make('stays')
                        ->schema([
                            Select::make('destination_id')
                                ->label('Overnight Destination')
                                ->options(fn (): array => Destination::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->required()
                                ->searchable(),
                            TextInput::make('check_in_day')->numeric()->required()->minValue(1),
                            TextInput::make('nights_count')->numeric()->required()->minValue(1)->default(1),
                            TextInput::make('notes')->maxLength(255),
                        ])
                        ->columns(4),
                ]),

            Section::make('Scheduled Departures & Capacity')
                ->collapsed()
                ->schema([
                    Repeater::make('departures')
                        ->schema([
                            TextInput::make('departure_code')->maxLength(64)->placeholder('Auto-generated if blank'),
                            DatePicker::make('start_date')->required(),
                            DatePicker::make('end_date')->required(),
                            TextInput::make('capacity')->numeric()->required()->minValue(1)->default(12),
                            Select::make('status')->options([
                                TourDeparture::STATUS_OPEN => 'Open',
                                TourDeparture::STATUS_FULL => 'Full',
                                TourDeparture::STATUS_CLOSED => 'Closed',
                                TourDeparture::STATUS_CANCELLED => 'Cancelled',
                            ])->default(TourDeparture::STATUS_OPEN),
                            TextInput::make('price_override_pkr')->numeric()->minValue(1),
                            TextInput::make('price_override_usd')->numeric()->minValue(1),
                            TextInput::make('notes')->maxLength(255),
                        ])
                        ->columns(4),
                ]),

            Section::make('Highlights, Inclusions, Exclusions, Policies & FAQs')
                ->collapsed()
                ->schema([
                    TagsInput::make('highlights'),
                    TagsInput::make('inclusions'),
                    TagsInput::make('exclusions'),
                    JoditEditor::make('cancellation_policy_html')->label('Policies & Cancellation Terms')->minHeight(180),
                    Repeater::make('faqs')
                        ->schema([
                            TextInput::make('question')->required()->maxLength(240),
                            Textarea::make('answer_html')->label('Answer (Sanitized HTML)')->rows(3)->required(),
                        ]),
                ]),

            Section::make('Media & SEO Metadata')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('hero_image_url')->maxLength(255),
                    TextInput::make('canonical_url')->maxLength(255),
                    TextInput::make('seo_title')->maxLength(180),
                    Toggle::make('noindex'),
                    Textarea::make('seo_description')->rows(2)->maxLength(320)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->color('gray')->searchable(),
                TextColumn::make('title')->searchable()->sortable()->limit(45),
                TextColumn::make('category')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => Tour::CATEGORIES[$state] ?? ucfirst($state)),
                TextColumn::make('duration_days')
                    ->label('Duration')
                    ->formatStateUsing(fn (int $state, Tour $record): string => "{$state}D / {$record->duration_nights}N"),
                TextColumn::make('pricing_display')
                    ->label('Starting Price')
                    ->state(fn (Tour $record): string => $record->displayPrice('PKR')),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Tour::STATUS_PUBLISHED => 'success',
                        Tour::STATUS_ARCHIVED => 'danger',
                        default => 'warning',
                    }),
                IconColumn::make('supports_nomad_addon')->label('Nomad')->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    Tour::STATUS_DRAFT => 'Draft',
                    Tour::STATUS_PUBLISHED => 'Published',
                    Tour::STATUS_ARCHIVED => 'Archived',
                ]),
                SelectFilter::make('category')->options(Tour::CATEGORIES),
                TernaryFilter::make('supports_nomad_addon')->label('Nomad Eligible'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTours::route('/'),
            'create' => Pages\CreateTour::route('/create'),
            'edit' => Pages\EditTour::route('/{record}/edit'),
        ];
    }
}
