<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Modules\Identity\Enums\AccountType;
use App\Modules\Rbac\Services\PrivilegeGuard;
use App\Modules\Rbac\Services\UserManagementService;
use App\Modules\Rbac\Support\RbacCatalog;
use App\Shared\Support\DisplayTimezone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Access Control';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account Details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(150),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(191)
                        ->unique(ignoreRecord: true),
                    TextInput::make('phone')
                        ->tel()
                        ->maxLength(32),
                    Select::make('account_type')
                        ->label('Account Type')
                        ->options([
                            AccountType::Staff->value => AccountType::Staff->label(),
                            AccountType::Customer->value => AccountType::Customer->label(),
                        ])
                        ->default(AccountType::Staff->value)
                        ->required()
                        ->disabledOn('edit'),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->minLength(12)
                        ->helperText('Leave blank on edit to keep existing password. Minimum 12 characters.'),
                    Toggle::make('is_active')
                        ->label('Active Account')
                        ->default(true)
                        ->visible(fn (): bool => auth()->user()?->can('users.deactivate') ?? false),
                ]),

            Section::make('Roles & Direct Permissions')
                ->description('Server-side privilege escalation guards enforce that you can only assign roles and permissions you hold.')
                ->visible(fn (): bool => auth()->user()?->can('roles.assign') ?? false)
                ->schema([
                    CheckboxList::make('role_names')
                        ->label('Assigned Roles')
                        ->options(function (?User $record): array {
                            /** @var User|null $actor */
                            $actor = auth()->user();
                            $guard = app(PrivilegeGuard::class);
                            $isSuperAdmin = $actor !== null && $guard->isSuperAdmin($actor);
                            $actorPerms = $actor?->getAllPermissions()->pluck('name')->all() ?? [];

                            $roles = Role::query()->where('guard_name', 'web')->with('permissions')->orderBy('name')->get();
                            $options = [];

                            foreach ($roles as $role) {
                                if ($record !== null && $record->isCustomer() && $role->name !== RbacCatalog::ROLE_CUSTOMER) {
                                    continue;
                                }
                                if ($record !== null && $record->isStaff() && $role->name === RbacCatalog::ROLE_CUSTOMER) {
                                    continue;
                                }
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
                        ->columns(3),

                    CheckboxList::make('direct_permission_names')
                        ->label('Direct Permissions (Exceptions Beyond Role)')
                        ->options(function (): array {
                            /** @var User|null $actor */
                            $actor = auth()->user();
                            $guard = app(PrivilegeGuard::class);

                            if ($actor !== null && $guard->isSuperAdmin($actor)) {
                                return Permission::query()->where('guard_name', 'web')->orderBy('name')->pluck('name', 'name')->all();
                            }

                            return $actor?->getAllPermissions()->pluck('name', 'name')->sort()->all() ?? [];
                        })
                        ->columns(3)
                        ->searchable(),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('User Profile')
                ->columns(3)
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('email'),
                    TextEntry::make('phone')->placeholder('—'),
                    TextEntry::make('account_type')
                        ->label('Account Type')
                        ->badge()
                        ->formatStateUsing(fn ($state): string => $state instanceof AccountType ? $state->label() : (string) $state),
                    TextEntry::make('is_active')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Deactivated')
                        ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                    TextEntry::make('last_login_at')
                        ->label('Last Login (PKT)')
                        ->formatStateUsing(fn ($state): string => DisplayTimezone::format($state)),
                ]),

            Section::make('User Permissions Preview')
                ->description('Breakdown of role-derived, direct, and combined effective permissions evaluated server-side.')
                ->schema([
                    TextEntry::make('roles_preview')
                        ->label('Assigned Roles')
                        ->badge()
                        ->color('primary')
                        ->state(fn (User $record): array => app(UserManagementService::class)->permissionBreakdown($record)['roles'])
                        ->placeholder('None'),

                    TextEntry::make('role_derived_permissions')
                        ->label('Role-Derived Permissions')
                        ->badge()
                        ->color('info')
                        ->state(fn (User $record): array => app(UserManagementService::class)->permissionBreakdown($record)['role_derived'])
                        ->placeholder('None'),

                    TextEntry::make('direct_permissions')
                        ->label('Direct Permissions')
                        ->badge()
                        ->color('warning')
                        ->state(fn (User $record): array => app(UserManagementService::class)->permissionBreakdown($record)['direct'])
                        ->placeholder('None'),

                    TextEntry::make('effective_permissions')
                        ->label('Effective Combined Permissions')
                        ->badge()
                        ->color('success')
                        ->state(fn (User $record): array => app(UserManagementService::class)->permissionBreakdown($record)['effective'])
                        ->placeholder('None'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AccountType ? $state->label() : (string) $state)
                    ->color(fn ($state): string => $state === AccountType::Staff ? 'primary' : 'gray'),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(', '),
                TextColumn::make('direct_permissions_count')
                    ->label('Direct Perms')
                    ->counts('permissions')
                    ->badge()
                    ->color('warning'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('last_login_at')
                    ->label('Last Login (PKT)')
                    ->formatStateUsing(fn ($state): string => DisplayTimezone::format($state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('account_type')
                    ->options([
                        AccountType::Staff->value => AccountType::Staff->label(),
                        AccountType::Customer->value => AccountType::Customer->label(),
                    ]),
                TernaryFilter::make('is_active')->label('Active Status'),
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()->label('Permissions'),
                EditAction::make(),
                Action::make('toggle_active')
                    ->label(fn (User $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => auth()->user()?->can('deactivate', $record) ?? false)
                    ->action(function (User $record): void {
                        try {
                            /** @var User $actor */
                            $actor = auth()->user();
                            app(UserManagementService::class)->setActiveStatus($actor, $record, ! $record->is_active);
                            Notification::make()->title('User status updated.')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
                DeleteAction::make()
                    ->using(function (User $record): void {
                        /** @var User $actor */
                        $actor = auth()->user();
                        app(UserManagementService::class)->deleteUser($actor, $record);
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
