<?php

namespace App\Filament\Resources\CmsPageResource\Pages;

use App\Filament\Resources\CmsPageResource;
use App\Models\User;
use App\Modules\Cms\Models\CmsPage;
use App\Modules\Cms\Models\CmsPageVersion;
use App\Modules\Cms\Services\PageBuilderService;
use App\Shared\Support\DisplayTimezone;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class EditCmsPage extends EditRecord
{
    protected static string $resource = CmsPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview_draft')
                ->label('Preview Draft')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (): string => route('cms.pages.preview', $this->getRecord()))
                ->openUrlInNewTab(),

            Action::make('publish_now')
                ->label('Publish Draft')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => auth()->user()?->can('publish', $this->getRecord()) ?? false)
                ->action(function (): void {
                    try {
                        /** @var User $actor */
                        $actor = auth()->user();
                        /** @var CmsPage $page */
                        $page = $this->getRecord();
                        app(PageBuilderService::class)->publishPage($actor, $page);
                        Notification::make()->title('Page published and cache invalidated.')->success()->send();
                        $this->refreshFormData(['status', 'published_at']);
                    } catch (Throwable $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),

            Action::make('schedule_publish')
                ->label('Schedule')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->can('publish', $this->getRecord()) ?? false)
                ->form([
                    DateTimePicker::make('scheduled_publish_at')
                        ->label('Publish Date & Time (UTC)')
                        ->required()
                        ->minDate(now()->addMinute()),
                ])
                ->action(function (array $data): void {
                    try {
                        /** @var User $actor */
                        $actor = auth()->user();
                        /** @var CmsPage $page */
                        $page = $this->getRecord();
                        app(PageBuilderService::class)->schedulePage(
                            $actor,
                            $page,
                            CarbonImmutable::parse($data['scheduled_publish_at'], 'UTC')
                        );
                        Notification::make()->title('Page publication scheduled.')->success()->send();
                    } catch (Throwable $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),

            Action::make('restore_version')
                ->label('Version History / Undo')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('info')
                ->visible(fn (): bool => auth()->user()?->can('update', $this->getRecord()) ?? false)
                ->form([
                    Select::make('version_id')
                        ->label('Restore Historical Version into New Draft')
                        ->required()
                        ->options(function (): array {
                            /** @var CmsPage $page */
                            $page = $this->getRecord();

                            return $page->versions()
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn (CmsPageVersion $v): array => [
                                    $v->id => "v{$v->version_number} ({$v->status}) — ".($v->label ?? 'Revision').' — '.DisplayTimezone::format($v->created_at),
                                ])
                                ->all();
                        }),
                ])
                ->action(function (array $data): void {
                    try {
                        /** @var User $actor */
                        $actor = auth()->user();
                        /** @var CmsPage $page */
                        $page = $this->getRecord();
                        $version = CmsPageVersion::query()->findOrFail($data['version_id']);
                        app(PageBuilderService::class)->restoreVersionAsDraft($actor, $page, $version);
                        Notification::make()->title("Restored v{$version->version_number} as a new draft.")->success()->send();
                        $this->redirect(static::getResource()::getUrl('edit', ['record' => $page]));
                    } catch (Throwable $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),

            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var CmsPage $page */
        $page = $this->getRecord();
        $page->load(['draftVersion.sections', 'publishedVersion.sections']);

        $activeVersion = $page->draftVersion ?? $page->publishedVersion;

        $data['sections'] = $activeVersion?->sections->map(fn ($section): array => [
            'section_key' => $section->section_key,
            'component_type' => $section->component_type,
            'is_visible' => (bool) $section->is_visible,
            'device_visibility' => $section->device_visibility,
            'style_preset' => $section->style_preset,
            'config' => $section->config ?? [],
            'config_json' => json_encode($section->config ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ])->all() ?? [];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();
        /** @var CmsPage $page */
        $page = $record;

        $sections = CreateCmsPage::normalizeSectionInputs($data['sections'] ?? []);

        return app(PageBuilderService::class)->saveDraft($actor, $page, $data, $sections);
    }
}
