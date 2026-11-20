<?php

namespace Tests\Feature\Phase14;

use App\Modules\Cms\Models\CmsPage;
use App\Modules\Migration\Services\WordPressImportService;
use Database\Seeders\CmsDefaultPagesSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordPressMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(CmsDefaultPagesSeeder::class);
    }

    public function test_dry_run_idempotent_import_sanitization_redirects_and_clean_batch_rollback(): void
    {
        $exportPath = tempnam(sys_get_temp_dir(), 'wp_exp_').'.json';
        file_put_contents($exportPath, json_encode([
            'items' => [
                [
                    'id' => '101',
                    'post_type' => 'page',
                    'title' => 'Karakoram Travel Tips',
                    'slug' => 'karakoram-travel-tips',
                    'url' => 'https://preciouspakistan.com/blog/karakoram-travel-tips/',
                    'content_html' => '<p>Bring layers for Khunjerab Pass.</p><script>alert("xss")</script>',
                ],
                [
                    'id' => '102',
                    'post_type' => 'page',
                    'title' => 'Incomplete Draft Story',
                    'slug' => 'incomplete-draft-story',
                    'url' => 'https://preciouspakistan.com/incomplete-draft-story/',
                    'content_html' => '',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $initialPageCount = CmsPage::query()->count();
        $service = app(WordPressImportService::class);

        // 1. Dry-run writes ZERO rows.
        $dryRun = $service->importFromFile($exportPath, true);
        $this->assertTrue($dryRun['dry_run']);
        $this->assertSame(2, $dryRun['total_items']);
        $this->assertSame(1, $dryRun['review_flagged']);
        $this->assertSame($initialPageCount, CmsPage::query()->count());

        // 2. Real import creates sanitized pages, preserves/redirects legacy URLs, and flags empty content as draft.
        $report = $service->importFromFile($exportPath, false);
        $this->assertSame(2, $report['imported']);
        $this->assertSame(1, $report['review_flagged']);
        $this->assertSame(1, $report['redirects_created']);

        $importedPage = CmsPage::query()->where('slug', 'karakoram-travel-tips')->firstOrFail();
        $this->assertSame(CmsPage::STATUS_PUBLISHED, $importedPage->status);
        $sectionHtml = $importedPage->publishedVersion->sections->first()->config['body_html'] ?? '';
        $this->assertStringContainsString('Khunjerab Pass', $sectionHtml);
        $this->assertStringNotContainsString('<script', $sectionHtml);

        $draftPage = CmsPage::query()->where('slug', 'incomplete-draft-story')->firstOrFail();
        $this->assertSame(CmsPage::STATUS_DRAFT, $draftPage->status);

        // Legacy URL 301 redirect works!
        $this->assertDatabaseHas('url_redirects', [
            'source_path' => '/blog/karakoram-travel-tips',
            'target_path' => '/karakoram-travel-tips',
            'status_code' => 301,
        ]);
        $this->get('/blog/karakoram-travel-tips')->assertRedirect('/karakoram-travel-tips');

        // 3. Re-running import is strictly idempotent (skips both duplicates).
        $repeat = $service->importFromFile($exportPath, false);
        $this->assertSame(0, $repeat['imported']);
        $this->assertSame(2, $repeat['skipped']);

        // 4. Batch rollback deletes ONLY imported batch records — pre-existing seeded pages remain untouched!
        $rollback = $service->rollbackBatch($report['batch_code']);
        $this->assertSame(2, $rollback['deleted_pages']);
        $this->assertSame(1, $rollback['deleted_redirects']);
        $this->assertSame($initialPageCount, CmsPage::query()->count());
        $this->assertDatabaseMissing('url_redirects', ['source_path' => '/blog/karakoram-travel-tips']);

        @unlink($exportPath);
    }
}
