<?php

namespace Tests\Feature\Phase2;

use App\Models\User;
use App\Modules\Identity\Services\AdminInvitationService;
use App\Modules\Rbac\Services\RoleManagementService;
use App\Modules\Rbac\Services\UserManagementService;
use App\Modules\Rbac\Support\RbacCatalog;
use Database\Seeders\RbacSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PrivilegeEscalationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_administrator_cannot_promote_user_to_super_admin(): void
    {
        $administrator = User::factory()->staff()->create();
        $administrator->assignRole(RbacCatalog::ROLE_ADMINISTRATOR);

        $bookingOfficer = User::factory()->staff()->create();
        $bookingOfficer->assignRole(RbacCatalog::ROLE_BOOKING_OFFICER);

        $this->expectException(AuthorizationException::class);

        app(UserManagementService::class)->syncAccess(
            actor: $administrator,
            target: $bookingOfficer,
            roleNames: [RbacCatalog::ROLE_SUPER_ADMIN],
        );
    }

    public function test_administrator_cannot_grant_direct_permission_they_do_not_possess(): void
    {
        $administrator = User::factory()->staff()->create();
        $administrator->assignRole(RbacCatalog::ROLE_ADMINISTRATOR);
        $this->assertFalse($administrator->can('invoices.void'));

        $financeOfficer = User::factory()->staff()->create();
        $financeOfficer->assignRole(RbacCatalog::ROLE_BOOKING_OFFICER);

        $this->expectException(AuthorizationException::class);

        app(UserManagementService::class)->syncAccess(
            actor: $administrator,
            target: $financeOfficer,
            roleNames: [RbacCatalog::ROLE_BOOKING_OFFICER],
            directPermissions: ['invoices.void'],
        );
    }

    public function test_administrator_cannot_assign_role_containing_permissions_they_lack(): void
    {
        $administrator = User::factory()->staff()->create();
        $administrator->assignRole(RbacCatalog::ROLE_ADMINISTRATOR);

        $staff = User::factory()->staff()->create();
        $staff->assignRole(RbacCatalog::ROLE_CUSTOMER_SUPPORT);

        // Finance Officer holds invoices.void, which Administrator does not hold.
        $this->expectException(AuthorizationException::class);

        app(UserManagementService::class)->syncAccess(
            actor: $administrator,
            target: $staff,
            roleNames: [RbacCatalog::ROLE_FINANCE_OFFICER],
        );
    }

    public function test_administrator_cannot_modify_or_deactivate_super_admin(): void
    {
        $superAdmin = User::factory()->staff()->create();
        $superAdmin->assignRole(RbacCatalog::ROLE_SUPER_ADMIN);

        $administrator = User::factory()->staff()->create();
        $administrator->assignRole(RbacCatalog::ROLE_ADMINISTRATOR);

        $this->expectException(AuthorizationException::class);

        app(UserManagementService::class)->setActiveStatus($administrator, $superAdmin, false);
    }

    public function test_administrator_cannot_self_modify_roles(): void
    {
        $administrator = User::factory()->staff()->create();
        $administrator->assignRole(RbacCatalog::ROLE_ADMINISTRATOR);

        $this->expectException(AuthorizationException::class);

        app(UserManagementService::class)->syncAccess(
            actor: $administrator,
            target: $administrator,
            roleNames: [RbacCatalog::ROLE_ADMINISTRATOR, RbacCatalog::ROLE_TOUR_MANAGER],
        );
    }

    public function test_administrator_cannot_create_role_with_permissions_above_their_own(): void
    {
        $administrator = User::factory()->staff()->create();
        $administrator->assignRole(RbacCatalog::ROLE_ADMINISTRATOR);

        $this->expectException(AuthorizationException::class);

        app(RoleManagementService::class)->createRole(
            actor: $administrator,
            name: 'Shadow Finance',
            permissionNames: ['invoices.view', 'invoices.void'],
        );
    }

    public function test_administrator_cannot_invite_super_admin(): void
    {
        $administrator = User::factory()->staff()->create();
        $administrator->assignRole(RbacCatalog::ROLE_ADMINISTRATOR);

        $this->expectException(AuthorizationException::class);

        app(AdminInvitationService::class)->createInvitation(
            inviter: $administrator,
            name: 'Escalated Admin',
            email: 'escalated@preciouspakistan.com',
            roleName: RbacCatalog::ROLE_SUPER_ADMIN,
        );
    }

    public function test_creating_hotel_partner_role_is_prohibited_even_for_super_admin(): void
    {
        $superAdmin = User::factory()->staff()->create();
        $superAdmin->assignRole(RbacCatalog::ROLE_SUPER_ADMIN);

        $this->expectException(InvalidArgumentException::class);

        app(RoleManagementService::class)->createRole(
            actor: $superAdmin,
            name: 'Hotel Partner',
            permissionNames: ['hotels.view'],
        );
    }

    public function test_customer_cannot_be_assigned_staff_role(): void
    {
        $superAdmin = User::factory()->staff()->create();
        $superAdmin->assignRole(RbacCatalog::ROLE_SUPER_ADMIN);

        $customer = User::factory()->create();
        $customer->assignRole(RbacCatalog::ROLE_CUSTOMER);

        $this->expectException(InvalidArgumentException::class);

        app(UserManagementService::class)->syncAccess(
            actor: $superAdmin,
            target: $customer,
            roleNames: [RbacCatalog::ROLE_TOUR_MANAGER],
        );
    }
}
