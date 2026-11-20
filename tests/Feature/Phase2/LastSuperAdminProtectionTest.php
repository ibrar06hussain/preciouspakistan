<?php

namespace Tests\Feature\Phase2;

use App\Models\User;
use App\Modules\Rbac\Services\RoleManagementService;
use App\Modules\Rbac\Services\UserManagementService;
use App\Modules\Rbac\Support\RbacCatalog;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LastSuperAdminProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_last_active_super_admin_cannot_be_deactivated(): void
    {
        $onlySuperAdmin = User::factory()->staff()->create();
        $onlySuperAdmin->assignRole(RbacCatalog::ROLE_SUPER_ADMIN);

        $this->expectException(InvalidArgumentException::class);

        app(UserManagementService::class)->setActiveStatus($onlySuperAdmin, $onlySuperAdmin, false);
    }

    public function test_last_active_super_admin_cannot_have_super_admin_role_removed(): void
    {
        $onlySuperAdmin = User::factory()->staff()->create();
        $onlySuperAdmin->assignRole(RbacCatalog::ROLE_SUPER_ADMIN);

        $this->expectException(InvalidArgumentException::class);

        app(UserManagementService::class)->syncAccess(
            actor: $onlySuperAdmin,
            target: $onlySuperAdmin,
            roleNames: [RbacCatalog::ROLE_ADMINISTRATOR],
        );
    }

    public function test_super_admin_can_be_deactivated_only_when_another_active_super_admin_exists(): void
    {
        $first = User::factory()->staff()->create();
        $first->assignRole(RbacCatalog::ROLE_SUPER_ADMIN);

        $second = User::factory()->staff()->create();
        $second->assignRole(RbacCatalog::ROLE_SUPER_ADMIN);

        $updated = app(UserManagementService::class)->setActiveStatus($first, $second, false);

        $this->assertFalse($updated->is_active);
        $this->assertTrue($first->fresh()->is_active);
    }

    public function test_protected_system_roles_cannot_be_deleted(): void
    {
        $superAdmin = User::factory()->staff()->create();
        $superAdmin->assignRole(RbacCatalog::ROLE_SUPER_ADMIN);

        $superAdminRole = Role::findByName(RbacCatalog::ROLE_SUPER_ADMIN, 'web');

        $this->expectException(InvalidArgumentException::class);

        app(RoleManagementService::class)->deleteRole($superAdmin, $superAdminRole);
    }
}
