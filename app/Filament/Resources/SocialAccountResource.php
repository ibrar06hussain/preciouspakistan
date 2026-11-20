<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialAccountResource\Pages;
use App\Modules\Communications\Models\SocialAccount;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
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

class SocialAccountResource extends Resource
{
    protected static ?string $model = SocialAccount::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-share';

    protected static string|UnitEnum|null $navigationGroup = 'Communications';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Social & WhatsApp Link Configuration')
                ->columns(2)
                ->schema([
                    Select::make('platform')->options(SocialAccount::PLATFORMS)->required(),
                    TextInput::make('label')->required()->maxLength(80),
                    TextInput::make('url')->url()->required()->maxLength(255)->columnSpanFull(),
                    TextInput::make('whatsapp_number')->placeholder('+923001234567')->helperText('Used for contextual wa.me click-to-chat inquiry buttons when platform is WhatsApp.'),
                    TextInput::make('sort_order')->numeric()->default(0),
                    CheckboxList::make('placements')->options([
                        'header' => 'Header',
                        'footer' => 'Footer',
                        'mobile' => 'Mobile Drawer',
                        'tour_detail' => 'Tour Detail CTA',
                    ])->columns(2),
                    Toggle::make('is_enabled')->default(true),
                    Toggle::make('open_in_new_tab')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('platform')->badge()->sortable(),
                TextColumn::make('label')->searchable(),
                TextColumn::make('url')->limit(40),
                TextColumn::make('sort_order')->sortable(),
                IconColumn::make('is_enabled')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialAccounts::route('/'),
            'create' => Pages\CreateSocialAccount::route('/create'),
            'edit' => Pages\EditSocialAccount::route('/{record}/edit'),
        ];
    }
}
