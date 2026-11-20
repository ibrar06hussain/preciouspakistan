<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\User;
use App\Modules\Rbac\Services\PrivilegeGuard;
use App\Modules\Rbac\Services\RoleManagementService;
use App\Modules\Rbac\Support\RbacCatalog;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Access Control';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Role Definition')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(64)
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?Role $record): bool => $record !== null && RbacCatalog::isProtectedSystemRole($record->name))
                        ->helperText('System roles (Super Admin, Customer) cannot be renamed or deleted. Hotel Partner roles are prohibited.'),
                ]),

            Section::make('Granular Permissions')
                ->description('Select permissions granted by this role. Non-Super-Admins may only assign permissions they already hold.')
                ->schema([
                    CheckboxList::make('permission_names')
                        ->label('Permissions')
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
                        ->searchable()
                        ->bulkToggleable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        RbacCatalog::ROLE_SUPER_ADMIN => 'danger',
                        RbacCatalog::ROLE_CUSTOMER => 'gray',
                        default => 'primary',
                    }),
                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Assigned Users')
                    ->counts('users')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->using(function (Role $record): void {
                        /** @var User $actor */
                        $actor = auth()->user();
                        app(RoleManagementService::class)->deleteRole($actor, $record);
                    }),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
