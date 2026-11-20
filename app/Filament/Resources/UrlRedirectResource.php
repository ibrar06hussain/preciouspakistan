<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UrlRedirectResource\Pages;
use App\Modules\Destinations\Models\UrlRedirect;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UrlRedirectResource extends Resource
{
    protected static ?string $model = UrlRedirect::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-right';

    protected static string|UnitEnum|null $navigationGroup = 'Content & SEO';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('301 / 302 URL Redirect')
                ->columns(2)
                ->schema([
                    TextInput::make('source_path')->required()->placeholder('/old-wordpress-slug')->maxLength(191),
                    TextInput::make('target_path')->required()->placeholder('/tours/hunza-attabad-passu-scenic-expedition')->maxLength(191),
                    Select::make('status_code')->options([301 => '301 Permanent', 302 => '302 Temporary'])->default(301)->required(),
                    TextInput::make('reason')->maxLength(120)->default('seo_redirect'),
                    Toggle::make('is_active')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source_path')->searchable()->sortable(),
                TextColumn::make('target_path')->searchable(),
                TextColumn::make('status_code')->badge(),
                TextColumn::make('hits')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUrlRedirects::route('/'),
            'create' => Pages\CreateUrlRedirect::route('/create'),
            'edit' => Pages\EditUrlRedirect::route('/{record}/edit'),
        ];
    }
}
