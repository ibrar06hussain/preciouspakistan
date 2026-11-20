<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FxRateSnapshotResource\Pages;
use App\Modules\Finance\Models\FxRateSnapshot;
use BackedEnum;
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

class FxRateSnapshotResource extends Resource
{
    protected static ?string $model = FxRateSnapshot::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & Finance';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Record FX Rate Snapshot')
                ->columns(2)
                ->schema([
                    TextInput::make('base_currency')->default('USD')->required()->maxLength(3),
                    TextInput::make('quote_currency')->default('PKR')->required()->maxLength(3),
                    TextInput::make('rate')->numeric()->required()->helperText('e.g., 278.500000 for 1 USD = 278.50 PKR'),
                    TextInput::make('source')->required()->placeholder('State Bank of Pakistan / Manual Treasury Override')->maxLength(120),
                    Toggle::make('is_manual_override')->default(true),
                    Textarea::make('notes')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('base_currency')->label('Base'),
                TextColumn::make('quote_currency')->label('Quote'),
                TextColumn::make('rate')->sortable(),
                TextColumn::make('source')->searchable(),
                IconColumn::make('is_manual_override')->boolean(),
                TextColumn::make('recorded_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('recorded_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFxRateSnapshots::route('/'),
            'create' => Pages\CreateFxRateSnapshot::route('/create'),
        ];
    }
}
