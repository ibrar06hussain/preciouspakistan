<?php

namespace App\Modules\Migration\Services;

use App\Modules\Cms\Models\CmsPage;
use App\Modules\Cms\Models\CmsPageSection;
use App\Modules\Cms\Models\CmsPageVersion;
use App\Modules\Cms\Support\HtmlSanitizerService;
use App\Modules\Destinations\Models\UrlRedirect;
use App\Modules\Migration\Models\WordPressImportBatch;
use App\Modules\Migration\Models\WordPressImportItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Non-destructive, idempotent WordPress content importer.
 * Reads ONLY authorized local WXR XML or JSON export files — NEVER connects to or modifies the live WordPress site/DB.
 */
final class WordPressImportService
{
    public function __construct(
        private readonly HtmlSanitizerService $sanitizer,
    ) {}

    /**
     * @return array{
     *     batch_code: string,
     *     dry_run: bool,
     *     total_items: int,
     *     imported: int,
     *     skipped: int,
     *     review_flagged: int,
     *     redirects_created: int,
     *     items: list<array<string, mixed>>
     * }
     */
    public function importFromFile(string $absolutePath, bool $dryRun = false): array
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw new InvalidArgumentException("Export file [{$absolutePath}] does not exist or is unreadable.");
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $format = $ext === 'json' ? 'json' : 'wxr_xml';
        $rawRecords = $format === 'json'
            ? $this->parseJsonExport($absolutePath)
            : $this->parseWxrXmlExport($absolutePath);

        $batchCode = 'WP-IMP-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4));
        $reportItems = [];
        $imported = 0;
        $skipped = 0;
        $reviewFlagged = 0;
        $redirectsCreated = 0;

        if ($dryRun) {
            foreach ($rawRecords as $record) {
                $alreadyImported = WordPressImportItem::query()
                    ->where('source_wp_id', $record['wp_id'])
                    ->where('status', 'imported')
                    ->exists();

                $warnings = $this->collectWarnings($record);
                $reportItems[] = [
                    'wp_id' => $record['wp_id'],
                    'slug' => $record['slug'],
                    'title' => $record['title'],
                    'action' => $alreadyImported ? 'skip_duplicate' : ($warnings !== [] ? 'import_with_review' : 'import'),
                    'warnings' => $warnings,
                ];
                if ($alreadyImported) {
                    $skipped++;
                } else {
                    $imported++;
                    if ($warnings !== []) {
                        $reviewFlagged++;
                    }
                }
            }

            return [
                'batch_code' => $batchCode.'-DRYRUN',
                'dry_run' => true,
                'total_items' => count($rawRecords),
                'imported' => $imported,
                'skipped' => $skipped,
                'review_flagged' => $reviewFlagged,
                'redirects_created' => 0,
                'items' => $reportItems,
            ];
        }

        return DB::transaction(function () use (
            $absolutePath,
            $format,
            $batchCode,
            $rawRecords,
            &$reportItems,
            &$imported,
            &$skipped,
            &$reviewFlagged,
            &$redirectsCreated,
        ): array {
            $batch = WordPressImportBatch::query()->create([
                'batch_code' => $batchCode,
                'source_path' => mb_substr($absolutePath, 0, 255),
                'source_format' => $format,
                'is_dry_run' => false,
                'status' => 'completed',
                'total_items' => count($rawRecords),
            ]);

            foreach ($rawRecords as $record) {
                $wpId = (string) $record['wp_id'];
                $alreadyImported = WordPressImportItem::query()
                    ->where('source_wp_id', $wpId)
                    ->where('status', 'imported')
                    ->exists();

                if ($alreadyImported) {
                    $skipped++;
                    WordPressImportItem::query()->create([
                        'batch_id' => $batch->id,
                        'source_wp_id' => $wpId,
                        'source_post_type' => $record['post_type'],
                        'source_url' => $record['url'],
                        'source_slug' => $record['slug'],
                        'status' => 'skipped_duplicate',
                    ]);
                    $reportItems[] = ['wp_id' => $wpId, 'slug' => $record['slug'], 'action' => 'skipped_duplicate'];

                    continue;
                }

                $warnings = $this->collectWarnings($record);
                if ($warnings !== []) {
                    $reviewFlagged++;
                }

                $cleanHtml = $this->sanitizeSafely($record['content_html']);
                $desiredSlug = Str::slug($record['slug'] ?: $record['title'] ?: ('wp-'.$wpId));
                $finalSlug = $desiredSlug;
                $redirectId = null;

                if (CmsPage::query()->where('slug', $finalSlug)->exists()) {
                    $finalSlug = $desiredSlug.'-wp-'.$wpId;
                }

                $page = CmsPage::query()->create([
                    'title' => mb_substr($record['title'] ?: "Imported WordPress Item #{$wpId}", 0, 160),
                    'slug' => $finalSlug,
                    'is_homepage' => false,
                    'status' => $warnings === [] ? CmsPage::STATUS_PUBLISHED : CmsPage::STATUS_DRAFT,
                    'published_at' => $warnings === [] ? now() : null,
                    'seo_title' => mb_substr($record['seo_title'] ?: $record['title'], 0, 160),
                    'seo_description' => mb_substr($record['seo_description'] ?: Str::limit(strip_tags($cleanHtml), 155), 0, 320),
                ]);

                $version = CmsPageVersion::query()->create([
                    'page_id' => $page->id,
                    'version_number' => 1,
                    'status' => $warnings === [] ? 'published' : 'draft',
                    'theme_preset' => 'emerald_karakoram',
                    'published_at' => $warnings === [] ? now() : null,
                ]);

                CmsPageSection::query()->create([
                    'page_version_id' => $version->id,
                    'section_key' => 'wp-body-1',
                    'component_type' => 'rich_text',
                    'sort_order' => 1,
                    'is_visible' => true,
                    'device_visibility' => 'all',
                    'config' => [
                        'heading' => $page->title,
                        'body_html' => $cleanHtml,
                    ],
                ]);

                $page->forceFill([
                    'draft_version_id' => $version->id,
                    'published_version_id' => $warnings === [] ? $version->id : null,
                ])->save();

                // Register 301 redirect if legacy WordPress URL path differs from final Laravel path.
                $legacyPath = $this->extractPath($record['url']);
                $targetPath = '/'.$finalSlug;
                if ($legacyPath !== null && $legacyPath !== '/' && $legacyPath !== $targetPath) {
                    $redirect = UrlRedirect::query()->updateOrCreate(
                        ['source_path' => $legacyPath],
                        [
                            'target_path' => $targetPath,
                            'status_code' => 301,
                            'reason' => "wp_import:{$batchCode}",
                            'is_active' => true,
                        ],
                    );
                    $redirectId = $redirect->id;
                    $redirectsCreated++;
                }

                WordPressImportItem::query()->create([
                    'batch_id' => $batch->id,
                    'source_wp_id' => $wpId,
                    'source_post_type' => $record['post_type'],
                    'source_url' => $record['url'],
                    'source_slug' => $record['slug'],
                    'target_type' => CmsPage::class,
                    'target_id' => $page->id,
                    'redirect_id' => $redirectId,
                    'status' => 'imported',
                    'warnings' => $warnings,
                ]);

                $imported++;
                $reportItems[] = [
                    'wp_id' => $wpId,
                    'slug' => $finalSlug,
                    'action' => 'imported',
                    'redirect_from' => $redirectId !== null ? $legacyPath : null,
                    'warnings' => $warnings,
                ];
            }

            $batch->forceFill([
                'imported_count' => $imported,
                'skipped_count' => $skipped,
                'review_flagged_count' => $reviewFlagged,
                'redirects_created_count' => $redirectsCreated,
                'report_summary' => $reportItems,
            ])->save();

            return [
                'batch_code' => $batchCode,
                'dry_run' => false,
                'total_items' => count($rawRecords),
                'imported' => $imported,
                'skipped' => $skipped,
                'review_flagged' => $reviewFlagged,
                'redirects_created' => $redirectsCreated,
                'items' => $reportItems,
            ];
        });
    }

    /**
     * Cleanly roll back ONLY records created by a specific WordPress import batch (never touches pre-existing site data!).
     *
     * @return array{batch_code: string, deleted_pages: int, deleted_redirects: int}
     */
    public function rollbackBatch(string $batchCode): array
    {
        $batch = WordPressImportBatch::query()->where('batch_code', $batchCode)->with('items')->first();
        if ($batch === null) {
            throw new RuntimeException("WordPress import batch [{$batchCode}] not found.");
        }

        if ($batch->status === 'rolled_back') {
            return ['batch_code' => $batchCode, 'deleted_pages' => 0, 'deleted_redirects' => 0];
        }

        return DB::transaction(function () use ($batch, $batchCode): array {
            $deletedPages = 0;
            $deletedRedirects = 0;

            foreach ($batch->items as $item) {
                if ($item->status !== 'imported') {
                    continue;
                }

                if ($item->target_type === CmsPage::class && $item->target_id !== null) {
                    $deletedPages += CmsPage::query()->where('id', $item->target_id)->delete();
                }

                if ($item->redirect_id !== null) {
                    $deletedRedirects += UrlRedirect::query()->where('id', $item->redirect_id)->delete();
                }

                $item->forceFill(['status' => 'rolled_back'])->save();
            }

            $batch->forceFill([
                'status' => 'rolled_back',
                'rolled_back_at' => now(),
            ])->save();

            return [
                'batch_code' => $batchCode,
                'deleted_pages' => $deletedPages,
                'deleted_redirects' => $deletedRedirects,
            ];
        });
    }

    /**
     * @return list<array{wp_id: string, post_type: string, title: string, slug: string, url: ?string, content_html: string, seo_title: string, seo_description: string}>
     */
    private function parseJsonExport(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Invalid JSON WordPress export file.');
        }

        $items = $decoded['items'] ?? $decoded;
        $out = [];
        foreach ($items as $idx => $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'wp_id' => (string) ($row['id'] ?? $row['wp_id'] ?? ($idx + 1)),
                'post_type' => (string) ($row['post_type'] ?? 'page'),
                'title' => trim((string) ($row['title'] ?? '')),
                'slug' => trim((string) ($row['slug'] ?? '')),
                'url' => isset($row['url']) ? trim((string) $row['url']) : null,
                'content_html' => (string) ($row['content_html'] ?? $row['content'] ?? ''),
                'seo_title' => trim((string) ($row['seo_title'] ?? '')),
                'seo_description' => trim((string) ($row['seo_description'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{wp_id: string, post_type: string, title: string, slug: string, url: ?string, content_html: string, seo_title: string, seo_description: string}>
     */
    private function parseWxrXmlExport(string $path): array
    {
        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path, 'SimpleXMLElement', LIBXML_NONET);
        libxml_use_internal_errors($prev);

        if ($xml === false) {
            throw new InvalidArgumentException('Invalid WordPress WXR XML export file.');
        }

        $out = [];
        foreach ($xml->channel->item ?? [] as $item) {
            $wp = $item->children('http://wordpress.org/export/1.2/');
            $content = $item->children('http://purl.org/rss/1.0/modules/content/');

            $out[] = [
                'wp_id' => trim((string) ($wp->post_id ?? Str::random(8))),
                'post_type' => trim((string) ($wp->post_type ?? 'page')),
                'title' => trim((string) ($item->title ?? '')),
                'slug' => trim((string) ($wp->post_name ?? '')),
                'url' => trim((string) ($item->link ?? '')) ?: null,
                'content_html' => (string) ($content->encoded ?? ''),
                'seo_title' => '',
                'seo_description' => '',
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return list<string>
     */
    private function collectWarnings(array $record): array
    {
        $warnings = [];
        if (trim((string) ($record['title'] ?? '')) === '') {
            $warnings[] = 'Missing title — requires editorial review before publishing.';
        }
        if (trim(strip_tags((string) ($record['content_html'] ?? ''))) === '') {
            $warnings[] = 'Empty content body — imported as draft for manual review.';
        }

        return $warnings;
    }

    private function sanitizeSafely(string $html): string
    {
        $stripped = preg_replace('/<\?(?:php|=)?.*?\?>/is', '', $html) ?? $html;
        $stripped = str_replace(['{{', '}}', '{!!', '!!}'], '', $stripped);

        return $this->sanitizer->sanitizeRichHtml($stripped, 'wp_import_content');
    }

    private function extractPath(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        return '/'.ltrim(rtrim($path, '/'), '/');
    }
}
