<?php

namespace Tests\Feature\Phase2;

use App\Models\User;
use App\Modules\Identity\Services\AdminInvitationService;
use App\Modules\Rbac\Models\AuditLog;
use App\Modules\Rbac\Services\RoleManagementService;
use App\Modules\Rbac\Services\UserManagementService;
use App\Modules\Rbac\Support\RbacCatalog;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use LogicException;
use Tests\TestCase;

class AuditTrailAndPermissionsPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_changing_user_roles_and_direct_permissions_writes_audit_snapshot_and_updates_preview(): void
    {
        $superAdmin = User::factory()->staff()->create(['email' => 'owner@preciouspakistan.com']);
        $superAdmin->assignRole(RbacCatalog::ROLE_SUPER_ADMIN);

        $staff = User::factory()->staff()->create(['email' => 'ops@preciouspakistan.com']);
        $staff->assignRole(RbacCatalog::ROLE_CUSTOMER_SUPPORT);

        $service = app(UserManagementService::class);
        $updated = $service->syncAccess(
            actor: $superAdmin,
            target: $staff,
            roleNames: [RbacCatalog::ROLE_BOOKING_OFFICER],
            directPermissions: ['seo.view'],
        );

        $breakdown = $service->permissionBreakdown($updated);

        $this->assertSame([RbacCatalog::ROLE_BOOKING_OFFICER], $breakdown['roles']);
        $this->assertContains('bookings.confirm', $breakdown['role_derived']);
        $this->assertNotContains('seo.view', $breakdown['role_derived']);
        $this->assertSame(['seo.view'], $breakdown['direct']);
        $this->assertContains('bookings.confirm', $breakdown['effective']);
        $this->assertContains('seo.view', $breakdown['effective']);

        $log = AuditLog::query()
            ->where('event', 'user.permissions_updated')
            ->where('target_user_id', $staff->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($superAdmin->id, $log->actor_id);
        $this->assertSame([RbacCatalog::ROLE_CUSTOMER_SUPPORT], $log->before_state['roles']);
        $this->assertSame([RbacCatalog::ROLE_BOOKING_OFFICER], $log->after_state['roles']);
        $this->assertSame(['seo.view'], $log->after_state['direct_permissions']);
    }

    public function test_custom_role_lifecycle_and_invitation_revocation_are_audited(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->staff()->create();
        $superAdmin->assignRole(RbacCatalog::ROLE_SUPER_ADMIN);

        $roleService = app(RoleManagementService::class);
        $customRole = $roleService->createRole($superAdmin, 'Northern Ops Coordinator', ['tours.view', 'bookings.view']);
        $roleService->updateRole($superAdmin, $customRole, 'Northern Ops Coordinator', ['tours.view', 'bookings.view', 'vehicles.view']);
        $roleService->deleteRole($superAdmin, $customRole);

        $this->assertDatabaseHas('audit_logs', ['event' => 'role.created', 'actor_id' => $superAdmin->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'role.updated', 'actor_id' => $superAdmin->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'role.deleted', 'actor_id' => $superAdmin->id]);

        $invitationService = app(AdminInvitationService::class);
        $created = $invitationService->createInvitation($superAdmin, 'Tariq Mehmood', 'tariq@preciouspakistan.com', RbacCatalog::ROLE_FLEET_MANAGER);
        $invitationService->revokeInvitation($superAdmin, $created['invitation']);

        $this->assertDatabaseHas('audit_logs', ['event' => 'invitation.created', 'actor_id' => $superAdmin->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'invitation.revoked', 'actor_id' => $superAdmin->id]);
    }

    public function test_audit_log_records_are_immutable(): void
    {
        $superAdmin = User::factory()->staff()->create();
        $superAdmin->assignRole(RbacCatalog::ROLE_SUPER_ADMIN);

        app(RoleManagementService::class)->createRole($superAdmin, 'Audit Test Role', ['tours.view']);
        $log = AuditLog::query()->firstOrFail();

        $this->expectException(LogicException::class);
        $log->update(['summary' => 'Tampered summary']);
    }
}
