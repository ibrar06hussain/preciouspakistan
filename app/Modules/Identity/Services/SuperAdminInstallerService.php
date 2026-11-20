<?php

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Enums\AccountType;
use App\Modules\Rbac\Support\RbacCatalog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class SuperAdminInstallerService
{
    public const SUPER_ADMIN_ROLE = 'Super Admin';

    /**
     * @var list<string>
     */
    private const FORBIDDEN_PASSWORDS = [
        'password',
        'password123',
        'password123!',
        'admin123',
        'admin123456!',
        '12345678',
        '123456789012',
        'precious123',
        'preciouspakistan',
        'qwerty123456',
    ];

    public function hasExistingSuperAdmin(): bool
    {
        $role = Role::query()->where('name', self::SUPER_ADMIN_ROLE)->first();

        if ($role === null) {
            return false;
        }

        return User::role(self::SUPER_ADMIN_ROLE)->exists();
    }

    public function validatePasswordStrength(string $password): void
    {
        $normalized = strtolower(trim($password));

        if (strlen($password) < 12) {
            throw new InvalidArgumentException('Super Admin password must be at least 12 characters long.');
        }

        if (in_array($normalized, self::FORBIDDEN_PASSWORDS, true)) {
            throw new InvalidArgumentException('Common or default passwords are not permitted for Super Admin.');
        }

        $hasUpper = preg_match('/[A-Z]/', $password) === 1;
        $hasLower = preg_match('/[a-z]/', $password) === 1;
        $hasDigit = preg_match('/\d/', $password) === 1;
        $hasSymbol = preg_match('/[^A-Za-z0-9]/', $password) === 1;

        if (! ($hasUpper && $hasLower && $hasDigit && $hasSymbol)) {
            throw new InvalidArgumentException('Super Admin password must include uppercase, lowercase, number, and symbol characters.');
        }
    }

    public function install(string $name, string $email, string $password): User
    {
        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid Super Admin name and email address are required.');
        }

        $this->validatePasswordStrength($password);

        if ($this->hasExistingSuperAdmin()) {
            throw new RuntimeException('A Super Admin account already exists. Use the admin portal to manage additional staff.');
        }

        if (User::query()->where('email', $email)->exists()) {
            throw new RuntimeException("A user with email [{$email}] already exists.");
        }

        return DB::transaction(function () use ($name, $email, $password): User {
            foreach (RbacCatalog::allPermissions() as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }

            $role = Role::findOrCreate(self::SUPER_ADMIN_ROLE, 'web');
            $role->syncPermissions(RbacCatalog::allPermissions());

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'account_type' => AccountType::Staff,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $user->assignRole($role);

            return $user->fresh('roles');
        });
    }
}
