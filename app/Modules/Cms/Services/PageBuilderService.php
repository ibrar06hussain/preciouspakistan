<?php

namespace App\Modules\Cms\Services;

use App\Models\User;
use App\Modules\Cms\Models\CmsPage;
use App\Modules\Cms\Models\CmsPageSection;
use App\Modules\Cms\Models\CmsPageVersion;
use App\Modules\Cms\Support\ComponentRegistry;
use App\Modules\Cms\Support\HtmlSanitizerService;
use App\Modules\Cms\Support\ThemePresets;
use App\Modules\Rbac\Services\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class PageBuilderService
{
    /**
     * Reserved route slugs that CMS pages must never hijack.
     *
     * @var list<string>
     */
    public const RESERVED_SLUGS = [
        'admin',
        'api',
        'dashboard',
        'login',
        'logout',
        'register',
        'forgot-password',
        'reset-password',
        'verify-email',
        'email',
        'cms',
        'livewire',
        'storage',
        'up',
    ];

    public function __construct(
        private readonly ComponentRegistry $registry,
        private readonly HtmlSanitizerService $sanitizer,
        private readonly PageRendererService $renderer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $pageData
     * @param  list<array<string, mixed>>  $sections
     *
     * @throws AuthorizationException
     */
    public function createPage(?User $actor, array $pageData, array $sections = [], bool $publishImmediately = false): CmsPage
    {
        if ($actor !== null && ! $actor->can('cms.update')) {
            throw new AuthorizationException('You do not have permission to create CMS pages.');
        }

        if ($publishImmediately && $actor !== null && ! $actor->can('cms.publish')) {
            throw new AuthorizationException('You do not have permission to publish CMS pages.');
        }

        $cleanPage = $this->sanitizePageAttributes($pageData);
        $themePreset = $cleanPage['theme_preset'] ?? null;
        unset($cleanPage['theme_preset']);
        $cleanSections = $this->sanitizeSections($sections);

        $page = DB::transaction(function () use ($actor, $cleanPage, $themePreset, $cleanSections, $publishImmediately): CmsPage {
            if ($cleanPage['is_homepage']) {
                CmsPage::query()->where('is_homepage', true)->update(['is_homepage' => false]);
            }

            $page = CmsPage::query()->create([
                ...$cleanPage,
                'status' => $publishImmediately ? CmsPage::STATUS_PUBLISHED : CmsPage::STATUS_DRAFT,
                'published_at' => $publishImmediately ? now() : null,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);

            $version = CmsPageVersion::query()->create([
                'page_id' => $page->id,
                'version_number' => 1,
                'label' => $publishImmediately ? 'Initial published version' : 'Initial draft',
                'status' => $publishImmediately ? CmsPage::STATUS_PUBLISHED : CmsPage::STATUS_DRAFT,
                'theme_preset' => $themePreset,
                'meta_snapshot' => $this->buildMetaSnapshot($page),
                'created_by' => $actor?->id,
                'published_by' => $publishImmediately ? $actor?->id : null,
                'published_at' => $publishImmediately ? now() : null,
            ]);

            $this->replaceVersionSections($version, $cleanSections);

            $page->forceFill([
                'draft_version_id' => $version->id,
                'published_version_id' => $publishImmediately ? $version->id : null,
            ])->save();

            if ($actor !== null) {
                $this->audit->record(
                    actor: $actor,
                    event: $publishImmediately ? 'cms.page_created_and_published' : 'cms.page_created',
                    summary: "Created CMS page [{$page->slug}]",
                    auditable: $page,
                    targetUser: null,
                    beforeState: null,
                    afterState: ['slug' => $page->slug, 'status' => $page->status, 'version' => 1],
                );
            }

            return $page->fresh(['draftVersion.sections', 'publishedVersion.sections']);
        });

        $this->renderer->invalidatePage($page);

        return $page;
    }

    /**
     * Save changes into a draft version strictly isolated from the published version.
     *
     * @param  array<string, mixed>  $pageData
     * @param  list<array<string, mixed>>  $sections
     *
     * @throws AuthorizationException
     */
    public function saveDraft(User $actor, CmsPage $page, array $pageData, array $sections, ?string $label = null): CmsPage
    {
        if (! $actor->can('cms.update')) {
            throw new AuthorizationException('You do not have permission to edit CMS pages.');
        }

        $cleanPage = $this->sanitizePageAttributes($pageData, $page);
        $cleanSections = $this->sanitizeSections($sections);
        $oldSlug = $page->slug;

        $updated = DB::transaction(function () use ($actor, $page, $cleanPage, $cleanSections, $label): CmsPage {
            $lockedPage = CmsPage::query()->lockForUpdate()->findOrFail($page->id);

            if ($cleanPage['is_homepage'] && ! $lockedPage->is_homepage) {
                CmsPage::query()->where('is_homepage', true)->where('id', '!=', $lockedPage->id)->update(['is_homepage' => false]);
            }

            $lockedPage->fill([
                'title' => $cleanPage['title'],
                'slug' => $cleanPage['slug'],
                'is_homepage' => $cleanPage['is_homepage'],
                'seo_title' => $cleanPage['seo_title'],
                'seo_description' => $cleanPage['seo_description'],
                'canonical_url' => $cleanPage['canonical_url'],
                'og_image_url' => $cleanPage['og_image_url'],
                'noindex' => $cleanPage['noindex'],
                'updated_by' => $actor->id,
            ])->save();

            // Always ensure draft editing NEVER mutates the currently published version row.
            $draftVersion = $lockedPage->draftVersion;
            if ($draftVersion === null || $draftVersion->id === $lockedPage->published_version_id || $draftVersion->status !== CmsPage::STATUS_DRAFT) {
                $nextVersionNumber = ((int) $lockedPage->versions()->max('version_number')) + 1;
                $draftVersion = CmsPageVersion::query()->create([
                    'page_id' => $lockedPage->id,
                    'version_number' => $nextVersionNumber,
                    'label' => $label ?? "Draft v{$nextVersionNumber}",
                    'status' => CmsPage::STATUS_DRAFT,
                    'theme_preset' => $cleanPage['theme_preset'] ?? null,
                    'meta_snapshot' => $this->buildMetaSnapshot($lockedPage),
                    'created_by' => $actor->id,
                ]);
                $lockedPage->forceFill(['draft_version_id' => $draftVersion->id])->save();
            } else {
                $draftVersion->forceFill([
                    'label' => $label ?? $draftVersion->label,
                    'theme_preset' => $cleanPage['theme_preset'] ?? $draftVersion->theme_preset,
                    'meta_snapshot' => $this->buildMetaSnapshot($lockedPage),
                ])->save();
            }

            $this->replaceVersionSections($draftVersion, $cleanSections);

            $this->audit->record(
                actor: $actor,
                event: 'cms.draft_saved',
                summary: "Saved draft v{$draftVersion->version_number} for [{$lockedPage->slug}]",
                auditable: $lockedPage,
                targetUser: null,
                beforeState: null,
                afterState: [
                    'version_number' => $draftVersion->version_number,
                    'sections_count' => count($cleanSections),
                ],
            );

            return $lockedPage->fresh(['draftVersion.sections', 'publishedVersion.sections']);
        });

        if ($oldSlug !== $updated->slug) {
            $this->renderer->invalidateSlug($oldSlug);
            $this->renderer->invalidatePage($updated);
        }

        return $updated;
    }

    /**
     * Clone a single section inside the page's active draft version.
     *
     * @throws AuthorizationException
     */
    public function cloneSection(User $actor, CmsPageSection $section): CmsPageSection
    {
        if (! $actor->can('cms.update')) {
            throw new AuthorizationException('You do not have permission to edit CMS sections.');
        }

        $version = $section->version()->with('page')->firstOrFail();
        if ($version->status !== CmsPage::STATUS_DRAFT) {
            throw new InvalidArgumentException('Sections can only be cloned inside a draft version.');
        }

        return DB::transaction(function () use ($version, $section): CmsPageSection {
            $maxOrder = (int) $version->sections()->max('sort_order');

            return $version->sections()->create([
                'section_key' => 'sec_'.Str::lower(Str::random(10)),
                'component_type' => $section->component_type,
                'sort_order' => $maxOrder + 1,
                'is_visible' => $section->is_visible,
                'device_visibility' => $section->device_visibility,
                'style_preset' => $section->style_preset,
                'config' => $section->config,
            ]);
        });
    }

    /**
     * Publish the current draft version (or specified version) and invalidate public cache.
     *
     * @throws AuthorizationException
     */
    public function publishPage(?User $actor, CmsPage $page, ?CmsPageVersion $version = null): CmsPage
    {
        if ($actor !== null && ! $actor->can('cms.publish')) {
            throw new AuthorizationException('You do not have permission to publish CMS pages.');
        }

        $published = DB::transaction(function () use ($actor, $page, $version): CmsPage {
            $lockedPage = CmsPage::query()->lockForUpdate()->findOrFail($page->id);
            $targetVersion = $version ?? $lockedPage->draftVersion;

            if ($targetVersion === null || $targetVersion->page_id !== $lockedPage->id) {
                throw new InvalidArgumentException('No valid page version found to publish.');
            }

            $previousPublishedId = $lockedPage->published_version_id;
            if ($previousPublishedId !== null && $previousPublishedId !== $targetVersion->id) {
                CmsPageVersion::query()
                    ->where('id', $previousPublishedId)
                    ->update(['status' => CmsPage::STATUS_ARCHIVED]);
            }

            $targetVersion->forceFill([
                'status' => CmsPage::STATUS_PUBLISHED,
                'published_by' => $actor?->id,
                'published_at' => now(),
                'meta_snapshot' => $this->buildMetaSnapshot($lockedPage),
            ])->save();

            $lockedPage->forceFill([
                'status' => CmsPage::STATUS_PUBLISHED,
                'published_version_id' => $targetVersion->id,
                'scheduled_publish_at' => null,
                'published_at' => now(),
                'updated_by' => $actor?->id ?? $lockedPage->updated_by,
            ])->save();

            $this->audit->record(
                actor: $actor,
                event: 'cms.page_published',
                summary: "Published [{$lockedPage->slug}] version v{$targetVersion->version_number}",
                auditable: $lockedPage,
                targetUser: null,
                beforeState: ['published_version_id' => $previousPublishedId],
                afterState: [
                    'published_version_id' => $targetVersion->id,
                    'version_number' => $targetVersion->version_number,
                ],
            );

            return $lockedPage->fresh(['draftVersion.sections', 'publishedVersion.sections']);
        });

        $this->renderer->invalidatePage($published);

        return $published;
    }

    /**
     * Schedule the current draft version for future publication.
     *
     * @throws AuthorizationException
     */
    public function schedulePage(User $actor, CmsPage $page, CarbonInterface $publishAt): CmsPage
    {
        if (! $actor->can('cms.publish')) {
            throw new AuthorizationException('You do not have permission to schedule CMS publication.');
        }

        if ($publishAt->isPast()) {
            throw new InvalidArgumentException('Scheduled publication time must be in the future.');
        }

        return DB::transaction(function () use ($actor, $page, $publishAt): CmsPage {
            $lockedPage = CmsPage::query()->lockForUpdate()->findOrFail($page->id);

            if ($lockedPage->draft_version_id === null) {
                throw new InvalidArgumentException('A draft version is required before scheduling publication.');
            }

            $lockedPage->forceFill([
                'status' => CmsPage::STATUS_SCHEDULED,
                'scheduled_publish_at' => $publishAt->toImmutable()->setTimezone('UTC'),
                'updated_by' => $actor->id,
            ])->save();

            $this->audit->record(
                actor: $actor,
                event: 'cms.page_scheduled',
                summary: "Scheduled [{$lockedPage->slug}] for {$publishAt->toIso8601String()}",
                auditable: $lockedPage,
                targetUser: null,
                beforeState: null,
                afterState: ['scheduled_publish_at' => $lockedPage->scheduled_publish_at?->toIso8601String()],
            );

            return $lockedPage->fresh();
        });
    }

    public function publishDueScheduledPages(): int
    {
        $duePages = CmsPage::query()
            ->where('status', CmsPage::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($duePages as $page) {
            $this->publishPage(null, $page);
            $count++;
        }

        return $count;
    }

    /**
     * Undo / restore any historical version into a fresh draft version for preview or republishing.
     *
     * @throws AuthorizationException
     */
    public function restoreVersionAsDraft(User $actor, CmsPage $page, CmsPageVersion $sourceVersion): CmsPageVersion
    {
        if (! $actor->can('cms.update')) {
            throw new AuthorizationException('You do not have permission to restore page versions.');
        }

        if ($sourceVersion->page_id !== $page->id) {
            throw new InvalidArgumentException('Selected version does not belong to this page.');
        }

        return DB::transaction(function () use ($actor, $page, $sourceVersion): CmsPageVersion {
            $lockedPage = CmsPage::query()->lockForUpdate()->findOrFail($page->id);
            $sourceVersion->load('sections');

            $nextVersionNumber = ((int) $lockedPage->versions()->max('version_number')) + 1;

            $newDraft = CmsPageVersion::query()->create([
                'page_id' => $lockedPage->id,
                'version_number' => $nextVersionNumber,
                'label' => "Restored from v{$sourceVersion->version_number}",
                'status' => CmsPage::STATUS_DRAFT,
                'theme_preset' => $sourceVersion->theme_preset,
                'meta_snapshot' => $sourceVersion->meta_snapshot,
                'created_by' => $actor->id,
            ]);

            foreach ($sourceVersion->sections as $index => $section) {
                $newDraft->sections()->create([
                    'section_key' => $section->section_key,
                    'component_type' => $section->component_type,
                    'sort_order' => $index,
                    'is_visible' => $section->is_visible,
                    'device_visibility' => $section->device_visibility,
                    'style_preset' => $section->style_preset,
                    'config' => $section->config,
                ]);
            }

            $lockedPage->forceFill([
                'draft_version_id' => $newDraft->id,
                'updated_by' => $actor->id,
            ])->save();

            $this->audit->record(
                actor: $actor,
                event: 'cms.version_restored',
                summary: "Restored [{$lockedPage->slug}] from v{$sourceVersion->version_number} to draft v{$nextVersionNumber}",
                auditable: $lockedPage,
                targetUser: null,
                beforeState: ['source_version' => $sourceVersion->version_number],
                afterState: ['new_draft_version' => $nextVersionNumber],
            );

            return $newDraft->fresh('sections');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizePageAttributes(array $data, ?CmsPage $existing = null): array
    {
        $title = $this->sanitizer->sanitizePlainText($data['title'] ?? $existing?->title ?? '', 'title', 180);
        if ($title === '') {
            throw new InvalidArgumentException('Page title is required.');
        }

        $isHomepage = (bool) ($data['is_homepage'] ?? $existing?->is_homepage ?? false);
        $rawSlug = (string) ($data['slug'] ?? $existing?->slug ?? Str::slug($title));
        $slug = $isHomepage && trim($rawSlug) === '' ? 'home' : Str::slug($rawSlug);

        if ($slug === '') {
            throw new InvalidArgumentException('A valid URL slug is required.');
        }

        if (! $isHomepage && in_array($slug, self::RESERVED_SLUGS, true)) {
            throw new InvalidArgumentException("The slug [{$slug}] is reserved by the application.");
        }

        $themePreset = $data['theme_preset'] ?? null;
        if ($themePreset !== null && $themePreset !== '' && ! array_key_exists($themePreset, ThemePresets::colorPresets())) {
            $themePreset = null;
        }

        return [
            'title' => $title,
            'slug' => $slug,
            'is_homepage' => $isHomepage,
            'seo_title' => $this->sanitizer->sanitizePlainText($data['seo_title'] ?? null, 'seo_title', 180) ?: null,
            'seo_description' => $this->sanitizer->sanitizePlainText($data['seo_description'] ?? null, 'seo_description', 320) ?: null,
            'canonical_url' => $this->sanitizer->sanitizeUrl($data['canonical_url'] ?? null, 'canonical_url') ?: null,
            'og_image_url' => $this->sanitizer->sanitizeUrl($data['og_image_url'] ?? null, 'og_image_url') ?: null,
            'noindex' => (bool) ($data['noindex'] ?? false),
            'theme_preset' => $themePreset,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    private function sanitizeSections(array $sections): array
    {
        $sanitized = [];
        $allowedStyles = array_keys(ThemePresets::sectionStylePresets());
        $allowedDevices = array_keys(ThemePresets::deviceVisibilityOptions());

        foreach (array_values($sections) as $index => $section) {
            if (! is_array($section)) {
                continue;
            }

            $type = (string) ($section['component_type'] ?? '');
            $config = is_array($section['config'] ?? null) ? $section['config'] : [];

            $validatedConfig = $this->registry->validateAndSanitize($type, $config);

            $stylePreset = (string) ($section['style_preset'] ?? 'default');
            if (! in_array($stylePreset, $allowedStyles, true)) {
                $stylePreset = 'default';
            }

            $deviceVisibility = (string) ($section['device_visibility'] ?? 'all');
            if (! in_array($deviceVisibility, $allowedDevices, true)) {
                $deviceVisibility = 'all';
            }

            $sectionKey = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($section['section_key'] ?? '')) ?: 'sec_'.Str::lower(Str::random(10));

            $sanitized[] = [
                'section_key' => mb_substr($sectionKey, 0, 64),
                'component_type' => $type,
                'sort_order' => $index,
                'is_visible' => (bool) ($section['is_visible'] ?? true),
                'device_visibility' => $deviceVisibility,
                'style_preset' => $stylePreset,
                'config' => $validatedConfig,
            ];
        }

        return $sanitized;
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     */
    private function replaceVersionSections(CmsPageVersion $version, array $sections): void
    {
        $version->sections()->delete();

        foreach ($sections as $section) {
            $version->sections()->create($section);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMetaSnapshot(CmsPage $page): array
    {
        return [
            'title' => $page->title,
            'slug' => $page->slug,
            'seo_title' => $page->seo_title,
            'seo_description' => $page->seo_description,
            'canonical_url' => $page->canonical_url,
            'og_image_url' => $page->og_image_url,
            'noindex' => (bool) $page->noindex,
        ];
    }
}
