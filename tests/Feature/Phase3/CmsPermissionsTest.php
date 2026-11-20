<?php

namespace Tests\Feature\Phase3;

use App\Models\User;
use App\Modules\Cms\Services\PageBuilderService;
use App\Modules\Rbac\Support\RbacCatalog;
use Database\Seeders\RbacSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CmsPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_content_seo_manager_can_access_cms_pages_menus_and_site_settings(): void
    {
        $contentManager = User::factory()->staff()->create();
        $contentManager->assignRole(RbacCatalog::ROLE_CONTENT_SEO_MANAGER);

        $this->actingAs($contentManager)->get('/admin/cms-pages')->assertOk();
        $this->actingAs($contentManager)->get('/admin/cms-menus')->assertOk();
        $this->actingAs($contentManager)->get('/admin/site-settings')->assertOk();
    }

    public function test_finance_officer_is_forbidden_from_cms_admin_routes(): void
    {
        $financeOfficer = User::factory()->staff()->create();
        $financeOfficer->assignRole(RbacCatalog::ROLE_FINANCE_OFFICER);

        $this->actingAs($financeOfficer)->get('/admin/cms-pages')->assertForbidden();
        $this->actingAs($financeOfficer)->get('/admin/cms-menus')->assertForbidden();
        $this->actingAs($financeOfficer)->get('/admin/site-settings')->assertForbidden();
    }

    public function test_draft_editor_without_publish_permission_can_save_draft_but_cannot_publish(): void
    {
        $draftOnlyRole = Role::findOrCreate('Draft Content Editor', 'web');
        $draftOnlyRole->syncPermissions(['cms.view', 'cms.update']);

        $draftEditor = User::factory()->staff()->create();
        $draftEditor->assignRole($draftOnlyRole);

        $builder = app(PageBuilderService::class);
        $page = $builder->createPage($draftEditor, [
            'title' => 'Draft Only Page',
            'slug' => 'draft-only-page',
        ], [
            ['component_type' => 'content_block', 'config' => ['heading' => 'Draft']],
        ], false);

        $this->assertSame('draft', $page->status);

        $this->expectException(AuthorizationException::class);
        $builder->publishPage($draftEditor, $page);
    }
}
