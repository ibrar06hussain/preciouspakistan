<?php

namespace App\Filament\Resources\CmsPageResource\Pages;

use App\Filament\Resources\CmsPageResource;
use App\Models\User;
use App\Modules\Cms\Services\PageBuilderService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCmsPage extends CreateRecord
{
    protected static string $resource = CmsPageResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();

        $sections = self::normalizeSectionInputs($data['sections'] ?? []);

        return app(PageBuilderService::class)->createPage($actor, $data, $sections, false);
    }

    /**
     * @param  array<int|string, mixed>  $rawSections
     * @return list<array<string, mixed>>
     */
    public static function normalizeSectionInputs(array $rawSections): array
    {
        $normalized = [];

        foreach (array_values($rawSections) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $config = is_array($item['config'] ?? null) ? $item['config'] : [];

            if (filled($item['config_json'] ?? null)) {
                $decoded = json_decode((string) $item['config_json'], true);
                if (is_array($decoded)) {
                    $config = array_merge($decoded, array_filter($config, fn ($v): bool => $v !== null && $v !== ''));
                }
            }

            $normalized[] = [
                'section_key' => $item['section_key'] ?? null,
                'component_type' => $item['component_type'] ?? '',
                'is_visible' => (bool) ($item['is_visible'] ?? true),
                'device_visibility' => $item['device_visibility'] ?? 'all',
                'style_preset' => $item['style_preset'] ?? 'default',
                'config' => $config,
            ];
        }

        return $normalized;
    }
}
