<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminInvitationResource\Pages;
use App\Models\User;
use App\Modules\Identity\Models\AdminInvitation;
use App\Modules\Identity\Services\AdminInvitationService;
use App\Modules\Rbac\Services\PrivilegeGuard;
use App\Modules\Rbac\Support\RbacCatalog;
use App\Shared\Support\DisplayTimezone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;
use Throwable;
use UnitEnum;

class AdminInvitationResource extends Resource
{
    protected static ?string $model = AdminInvitation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|UnitEnum|null $navigationGroup = 'Access Control';

    protected static ?string $navigationLabel = 'Staff Invitations';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Invite Company Staff Member')
                ->description('Invitations expire in 72 hours and store only a SHA-256 token hash. Hotel partner accounts are not permitted.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(150),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(191),
                    Select::make('role_name')
                        ->label('Staff Role')
                        ->required()
                        ->options(function (): array {
                            /** @var User|null $actor */
                            $actor = auth()->user();
                            $guard = app(PrivilegeGuard::class);
                            $isSuperAdmin = $actor !== null && $guard->isSuperAdmin($actor);
                            $actorPerms = $actor?->getAllPermissions()->pluck('name')->all() ?? [];

                            $roles = Role::query()
                                ->where('guard_name', 'web')
                                ->where('name', '!=', RbacCatalog::ROLE_CUSTOMER)
                                ->with('permissions')
                                ->orderBy('name')
                                ->get();

                            $options = [];
                            foreach ($roles as $role) {
                                if (! $isSuperAdmin) {
                                    if ($role->name === RbacCatalog::ROLE_SUPER_ADMIN) {
                                        continue;
                                    }
                                    if (array_diff($role->permissions->pluck('name')->all(), $actorPerms) !== []) {
                                        continue;
                                    }
                                }
                                $options[$role->name] = $role->name;
                            }

                            return $options;
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('role_name')->label('Role')->badge()->color('primary'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(function (AdminInvitation $record): string {
                        if ($record->accepted_at !== null) {
                            return 'Accepted';
                        }
                        if ($record->revoked_at !== null) {
                            return 'Revoked';
                        }
                        if ($record->expires_at->isPast()) {
                            return 'Expired';
                        }

                        return 'Pending';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Accepted' => 'success',
                        'Pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('inviter.name')->label('Invited By')->placeholder('System'),
                TextColumn::make('expires_at')
                    ->label('Expires (PKT)')
                    ->formatStateUsing(fn ($state): string => DisplayTimezone::format($state))
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Revoke')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (AdminInvitation $record): bool => auth()->user()?->can('revoke', $record) ?? false)
                    ->action(function (AdminInvitation $record): void {
                        try {
                            /** @var User $actor */
                            $actor = auth()->user();
                            app(AdminInvitationService::class)->revokeInvitation($actor, $record);
                            Notification::make()->title('Invitation revoked.')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminInvitations::route('/'),
            'create' => Pages\CreateAdminInvitation::route('/create'),
        ];
    }
}
