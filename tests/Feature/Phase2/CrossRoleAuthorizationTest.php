<?php

namespace Tests\Feature\Phase2;

use App\Models\User;
use App\Modules\Rbac\Support\RbacCatalog;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossRoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_finance_officer_cannot_access_users_roles_invitations_or_audit_logs(): void
    {
        $financeOfficer = User::factory()->staff()->create();
        $financeOfficer->assignRole(RbacCatalog::ROLE_FINANCE_OFFICER);

        $this->actingAs($financeOfficer)->get('/admin')->assertOk();
        $this->actingAs($financeOfficer)->get('/admin/users')->assertForbidden();
        $this->actingAs($financeOfficer)->get('/admin/roles')->assertForbidden();
        $this->actingAs($financeOfficer)->get('/admin/admin-invitations')->assertForbidden();
        $this->actingAs($financeOfficer)->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_content_seo_manager_has_cms_and_seo_permissions_only_and_is_denied_rbac_pages(): void
    {
        $contentManager = User::factory()->staff()->create();
        $contentManager->assignRole(RbacCatalog::ROLE_CONTENT_SEO_MANAGER);

        $this->assertTrue($contentManager->can('cms.publish'));
        $this->assertTrue($contentManager->can('seo.update'));
        $this->assertFalse($contentManager->can('invoices.issue'));
        $this->assertFalse($contentManager->can('vehicles.rates.manage'));
        $this->assertFalse($contentManager->can('roles.assign'));

        $this->actingAs($contentManager)->get('/admin/users')->assertForbidden();
        $this->actingAs($contentManager)->get('/admin/roles')->assertForbidden();
        $this->actingAs($contentManager)->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_super_admin_can_access_all_rbac_and_audit_resources(): void
    {
        $superAdmin = User::factory()->staff()->create();
        $superAdmin->assignRole(RbacCatalog::ROLE_SUPER_ADMIN);

        $this->actingAs($superAdmin)->get('/admin/users')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/roles')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/admin-invitations')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/audit-logs')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/users/'.$superAdmin->id)->assertOk()->assertSee('User Permissions Preview');
    }

    public function test_administrator_can_view_users_and_roles_but_cannot_edit_super_admin_record(): void
    {
        $superAdmin = User::factory()->staff()->create();
        $superAdmin->assignRole(RbacCatalog::ROLE_SUPER_ADMIN);

        $administrator = User::factory()->staff()->create();
        $administrator->assignRole(RbacCatalog::ROLE_ADMINISTRATOR);

        $this->actingAs($administrator)->get('/admin/users')->assertOk();
        $this->actingAs($administrator)->get('/admin/roles')->assertOk();
        $this->actingAs($administrator)->get('/admin/users/'.$superAdmin->id.'/edit')->assertForbidden();
    }
}
