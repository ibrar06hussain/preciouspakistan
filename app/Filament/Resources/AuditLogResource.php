<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Modules\Rbac\Models\AuditLog;
use App\Shared\Support\DisplayTimezone;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Access Control';

    protected static ?string $navigationLabel = 'Audit Trail';

    protected static ?int $navigationSort = 4;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Audit Event')
                ->columns(3)
                ->schema([
                    TextEntry::make('event')->badge()->color('primary'),
                    TextEntry::make('actor.email')->label('Actor')->placeholder('System'),
                    TextEntry::make('targetUser.email')->label('Target User')->placeholder('—'),
                    TextEntry::make('summary')->columnSpanFull(),
                    TextEntry::make('ip_address')->label('IP Address')->placeholder('—'),
                    TextEntry::make('created_at')
                        ->label('Recorded At (PKT)')
                        ->formatStateUsing(fn ($state): string => DisplayTimezone::format($state)),
                ]),

            Section::make('State Snapshots')
                ->columns(2)
                ->schema([
                    TextEntry::make('before_state')
                        ->label('Before State')
                        ->formatStateUsing(fn ($state): string => $state ? (string) json_encode($state, JSON_PRETTY_PRINT) : '—'),
                    TextEntry::make('after_state')
                        ->label('After State')
                        ->formatStateUsing(fn ($state): string => $state ? (string) json_encode($state, JSON_PRETTY_PRINT) : '—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('When (PKT)')
                    ->formatStateUsing(fn ($state): string => DisplayTimezone::format($state))
                    ->sortable(),
                TextColumn::make('event')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'deleted'), str_contains($state, 'deactivated'), str_contains($state, 'revoked') => 'danger',
                        str_contains($state, 'permissions') => 'warning',
                        default => 'info',
                    })
                    ->searchable(),
                TextColumn::make('actor.email')
                    ->label('Actor')
                    ->searchable()
                    ->placeholder('System'),
                TextColumn::make('targetUser.email')
                    ->label('Target User')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('summary')
                    ->searchable()
                    ->limit(60),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'user.created' => 'User Created',
                        'user.updated' => 'User Updated',
                        'user.permissions_updated' => 'User Permissions Updated',
                        'user.activated' => 'User Activated',
                        'user.deactivated' => 'User Deactivated',
                        'user.deleted' => 'User Deleted',
                        'role.created' => 'Role Created',
                        'role.updated' => 'Role Updated',
                        'role.deleted' => 'Role Deleted',
                        'invitation.created' => 'Invitation Created',
                        'invitation.revoked' => 'Invitation Revoked',
                        'invitation.accepted' => 'Invitation Accepted',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
