<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaAssetResource\Pages;
use App\Modules\Media\Models\MediaAsset;
use App\Modules\Media\Services\MediaLibraryService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use RuntimeException;
use UnitEnum;

class MediaAssetResource extends Resource
{
    protected static ?string $model = MediaAsset::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|UnitEnum|null $navigationGroup = 'Content & SEO';

    protected static ?int $navigationSort = 30;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Media SEO & Focal Point')
                ->columns(2)
                ->schema([
                    TextInput::make('alt_text')->label('Alt Text (Required for SEO & Accessibility)')->maxLength(255),
                    TextInput::make('caption')->maxLength(500),
                    TextInput::make('license_attribution')->maxLength(255),
                    TextInput::make('focal_x')->numeric()->default(50),
                    TextInput::make('focal_y')->numeric()->default(50),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_filename')->searchable()->sortable()->limit(32),
                TextColumn::make('mime_type')->badge(),
                TextColumn::make('width')->label('Dims')->formatStateUsing(fn ($state, MediaAsset $r): string => ($r->width && $r->height) ? "{$r->width}×{$r->height}" : '—'),
                IconColumn::make('webp_path')->label('WebP')->boolean(fn (MediaAsset $r): bool => $r->webp_path !== null),
                IconColumn::make('avif_path')->label('AVIF')->boolean(fn (MediaAsset $r): bool => $r->avif_path !== null),
                TextColumn::make('alt_text')->placeholder('Missing Alt!')->limit(30),
                TextColumn::make('usages_count')->counts('usages')->label('Refs'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('delete_asset')
                    ->label('Delete')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(function (MediaAsset $record): void {
                        try {
                            app(MediaLibraryService::class)->deleteAsset(auth()->user(), $record);
                            Notification::make()->title('Media asset deleted.')->success()->send();
                        } catch (RuntimeException $e) {
                            Notification::make()->title('Cannot Delete Referenced Asset')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMediaAssets::route('/'),
        ];
    }
}
