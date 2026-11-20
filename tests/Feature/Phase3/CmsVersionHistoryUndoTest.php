<?php

namespace Tests\Feature\Phase3;

use App\Models\User;
use App\Modules\Cms\Services\PageBuilderService;
use App\Modules\Rbac\Support\RbacCatalog;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsVersionHistoryUndoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_admin_can_undo_to_previous_version_and_clone_sections(): void
    {
        $editor = User::factory()->staff()->create();
        $editor->assignRole(RbacCatalog::ROLE_CONTENT_SEO_MANAGER);

        $builder = app(PageBuilderService::class);

        $page = $builder->createPage($editor, [
            'title' => 'Gilgit Overview',
            'slug' => 'gilgit-overview',
        ], [
            [
                'component_type' => 'content_block',
                'config' => ['heading' => 'Version One Content', 'body_html' => '<p>First release</p>'],
            ],
        ], true);

        $v1 = $page->publishedVersion;

        // Create and publish v2.
        $builder->saveDraft($editor, $page, [
            'title' => 'Gilgit Overview',
            'slug' => 'gilgit-overview',
        ], [
            [
                'component_type' => 'content_block',
                'config' => ['heading' => 'Version Two Content', 'body_html' => '<p>Second release</p>'],
            ],
        ]);
        $builder->publishPage($editor, $page->fresh());

        $this->get('/gilgit-overview')->assertOk()->assertSee('Version Two Content');

        // Restore v1 as a new draft v3 (undo), clone its section, and publish v3.
        $restoredDraft = $builder->restoreVersionAsDraft($editor, $page->fresh(), $v1);
        $this->assertSame(3, $restoredDraft->version_number);

        $firstSection = $restoredDraft->sections()->firstOrFail();
        $cloned = $builder->cloneSection($editor, $firstSection);
        $this->assertSame(2, $restoredDraft->sections()->count());
        $this->assertNotSame($firstSection->section_key, $cloned->section_key);

        $builder->publishPage($editor, $page->fresh());

        $this->get('/gilgit-overview')
            ->assertOk()
            ->assertSee('Version One Content')
            ->assertDontSee('Version Two Content');
    }
}
