<?php

namespace Tests\Feature\Phase2;

use App\Modules\Rbac\Support\RbacCatalog;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_rbac_seeder_creates_all_eleven_initial_roles_and_granular_permissions(): void
    {
        $this->seed(RbacSeeder::class);

        $expectedRoles = [
            'Super Admin',
            'Administrator',
            'Tour Manager',
            'Booking Officer',
            'Hotel/Accommodation Manager',
            'Fleet Manager',
            'Digital Nomad Manager',
            'Finance Officer',
            'Content/SEO Manager',
            'Customer Support',
            'Customer',
        ];

        $this->assertSame(11, Role::query()->count());

        foreach ($expectedRoles as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName, 'guard_name' => 'web']);
        }

        // No Hotel Partner role may exist on this company-operated platform.
        $this->assertDatabaseMissing('roles', ['name' => 'Hotel Partner']);

        foreach (RbacCatalog::allPermissions() as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdminRole = Role::findByName('Super Admin', 'web');
        $this->assertSame(Permission::query()->count(), $superAdminRole->permissions()->count());

        $customerRole = Role::findByName('Customer', 'web');
        $this->assertSame(0, $customerRole->permissions()->count());
    }
}
