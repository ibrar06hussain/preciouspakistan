<?php

namespace Database\Seeders;

use App\Modules\Rbac\Support\RbacCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RbacCatalog::allPermissions() as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach (RbacCatalog::initialRoles() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
