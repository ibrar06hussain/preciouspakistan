<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CmsMenuResource\Pages;
use App\Modules\Cms\Models\CmsMenu;
use App\Modules\Cms\Support\ThemePresets;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
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

class CmsMenuResource extends Resource
{
    protected static ?string $model = CmsMenu::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static string|UnitEnum|null $navigationGroup = 'CMS & Content';

    protected static ?string $navigationLabel = 'Navigation Menus';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Menu Location')
                ->columns(3)
                ->schema([
                    TextInput::make('name')->required()->maxLength(120),
                    Select::make('location')
                        ->required()
                        ->options([
                            CmsMenu::LOCATION_HEADER => 'Header Primary',
                            CmsMenu::LOCATION_MOBILE => 'Mobile Drawer',
                            CmsMenu::LOCATION_FOOTER_PRIMARY => 'Footer Explore',
                            CmsMenu::LOCATION_FOOTER_LEGAL => 'Footer Legal',
                        ])
                        ->unique(ignoreRecord: true),
                    Toggle::make('is_active')->default(true),
                ]),

            Section::make('Menu Items')
                ->schema([
                    Repeater::make('menu_items')
                        ->label('Links')
                        ->reorderable()
                        ->cloneable()
                        ->schema([
                            TextInput::make('label')->required()->maxLength(120),
                            TextInput::make('url')->required()->maxLength(255)->placeholder('/about or https://...'),
                            Select::make('target')
                                ->options(['_self' => 'Same Tab', '_blank' => 'New Tab'])
                                ->default('_self'),
                            Select::make('device_visibility')
                                ->options(ThemePresets::deviceVisibilityOptions())
                                ->default('all'),
                            Toggle::make('is_visible')->default(true),
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
                TextColumn::make('location')->badge()->color('primary'),
                TextColumn::make('items_count')->label('Links')->counts('items')->badge(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCmsMenus::route('/'),
            'create' => Pages\CreateCmsMenu::route('/create'),
            'edit' => Pages\EditCmsMenu::route('/{record}/edit'),
        ];
    }
}
