<?php

namespace Tests\Feature\Phase3;

use App\Models\User;
use App\Modules\Cms\Services\PageBuilderService;
use App\Modules\Rbac\Support\RbacCatalog;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsDraftIsolationAndPublishTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_draft_page_is_hidden_from_public_until_published_and_previewable_by_cms_staff_with_noindex(): void
    {
        $editor = User::factory()->staff()->create();
        $editor->assignRole(RbacCatalog::ROLE_CONTENT_SEO_MANAGER);

        $builder = app(PageBuilderService::class);
        $page = $builder->createPage($editor, [
            'title' => 'Karakoram Travel Guide',
            'slug' => 'karakoram-guide',
        ], [
            [
                'component_type' => 'content_block',
                'style_preset' => 'surface',
                'config' => [
                    'heading' => 'Draft Guide Heading',
                    'body_html' => '<p>Unpublished draft details.</p>',
                ],
            ],
        ], false);

        // Guests get 404 for unpublished draft page.
        $this->get('/karakoram-guide')->assertNotFound();

        // Guests cannot access preview route.
        $this->get(route('cms.pages.preview', $page))->assertRedirect(route('login'));

        // Authorized CMS staff can preview draft with noindex header.
        $preview = $this->actingAs($editor)->get(route('cms.pages.preview', $page));
        $preview->assertOk();
        $preview->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $preview->assertSee('Draft Preview Mode');
        $preview->assertSee('Draft Guide Heading');

        // Publish makes it live publicly.
        $builder->publishPage($editor, $page);

        $this->get('/karakoram-guide')
            ->assertOk()
            ->assertSee('Draft Guide Heading')
            ->assertDontSee('Draft Preview Mode');
    }

    public function test_editing_published_page_isolates_draft_from_live_visitors_and_invalidates_cache_on_publish(): void
    {
        $editor = User::factory()->staff()->create();
        $editor->assignRole(RbacCatalog::ROLE_CONTENT_SEO_MANAGER);

        $builder = app(PageBuilderService::class);
        $page = $builder->createPage($editor, [
            'title' => 'Seasonal Dispatch',
            'slug' => 'seasonal-dispatch',
        ], [
            [
                'component_type' => 'hero_banner',
                'config' => ['heading' => 'Published Spring Version'],
            ],
        ], true);

        // Warm public cache.
        $this->get('/seasonal-dispatch')->assertOk()->assertSee('Published Spring Version');

        // Save new draft edits without publishing.
        $builder->saveDraft($editor, $page, [
            'title' => 'Seasonal Dispatch',
            'slug' => 'seasonal-dispatch',
        ], [
            [
                'component_type' => 'hero_banner',
                'config' => ['heading' => 'Unpublished Autumn Draft'],
            ],
        ]);

        // Live public page still serves the published version.
        $this->get('/seasonal-dispatch')
            ->assertOk()
            ->assertSee('Published Spring Version')
            ->assertDontSee('Unpublished Autumn Draft');

        // Preview shows the new draft.
        $this->actingAs($editor)
            ->get(route('cms.pages.preview', $page))
            ->assertOk()
            ->assertSee('Unpublished Autumn Draft');

        // Publish immediately invalidates cache and serves updated content.
        $builder->publishPage($editor, $page->fresh());

        $this->get('/seasonal-dispatch')
            ->assertOk()
            ->assertSee('Unpublished Autumn Draft')
            ->assertDontSee('Published Spring Version');
    }

    public function test_scheduled_page_publishes_automatically_when_due(): void
    {
        $editor = User::factory()->staff()->create();
        $editor->assignRole(RbacCatalog::ROLE_CONTENT_SEO_MANAGER);

        $builder = app(PageBuilderService::class);
        $page = $builder->createPage($editor, [
            'title' => 'Winter Expedition Launch',
            'slug' => 'winter-expedition',
        ], [
            [
                'component_type' => 'hero_banner',
                'config' => ['heading' => 'Scheduled Winter Launch'],
            ],
        ], false);

        $builder->schedulePage($editor, $page, now()->addHour());
        $this->get('/winter-expedition')->assertNotFound();

        // Advance scheduled_publish_at into the past and run scheduler command.
        $page->forceFill(['scheduled_publish_at' => now()->subMinute()])->save();
        $this->artisan('cms:publish-scheduled')->assertSuccessful();

        $this->get('/winter-expedition')
            ->assertOk()
            ->assertSee('Scheduled Winter Launch');
    }
}
