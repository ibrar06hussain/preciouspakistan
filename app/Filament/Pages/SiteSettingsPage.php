<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Modules\Cms\Services\SiteSettingsService;
use App\Modules\Cms\Support\HtmlSanitizerService;
use App\Modules\Cms\Support\ThemePresets;
use App\Modules\Rbac\Services\AuditLogger;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Throwable;
use UnitEnum;

class SiteSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static string|UnitEnum|null $navigationGroup = 'CMS & Content';

    protected static ?string $navigationLabel = 'Site Settings & Theme';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'site-settings';

    protected string $view = 'filament.pages.site-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->isActiveStaff() && $user->can('cms.update');
    }

    public function mount(SiteSettingsService $settings): void
    {
        $all = $settings->allPublic();

        $this->form->fill([
            'site_name' => $all['site_name'] ?? 'Precious Pakistan',
            'site_tagline' => $all['site_tagline'] ?? '',
            'brand_logo_url' => $all['brand_logo_url'] ?? '',
            'theme_color_preset' => $all['theme_color_preset'] ?? 'karakoram_emerald',
            'theme_typography_preset' => $all['theme_typography_preset'] ?? 'modern_editorial',
            'announcement_enabled' => (bool) ($all['announcement_enabled'] ?? false),
            'announcement_text' => $all['announcement_text'] ?? '',
            'support_email' => $all['support_email'] ?? 'bookings@preciouspakistan.com',
            'operating_hours' => $all['operating_hours'] ?? '',
            'footer_about' => $all['footer_about'] ?? '',
            'footer_copyright' => $all['footer_copyright'] ?? '',
            'social_links' => is_array($all['social_links'] ?? null) ? $all['social_links'] : [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Brand Identity & Safe Visual Presets')
                    ->columns(2)
                    ->schema([
                        TextInput::make('site_name')->required()->maxLength(120),
                        TextInput::make('site_tagline')->maxLength(220),
                        TextInput::make('brand_logo_url')
                            ->label('Brand Logo URL (Optional)')
                            ->placeholder('/storage/uploads/logo.webp')
                            ->maxLength(255),
                        Select::make('theme_color_preset')
                            ->label('Color Preset')
                            ->options(collect(ThemePresets::colorPresets())->mapWithKeys(fn ($v, $k) => [$k => $v['label']])->all())
                            ->required(),
                        Select::make('theme_typography_preset')
                            ->label('Typography Preset')
                            ->options(collect(ThemePresets::typographyPresets())->mapWithKeys(fn ($v, $k) => [$k => $v['label']])->all())
                            ->required(),
                    ]),

                Section::make('Announcement Bar & Operations')
                    ->columns(2)
                    ->schema([
                        Toggle::make('announcement_enabled')->label('Show Top Announcement Bar'),
                        TextInput::make('support_email')->email()->required(),
                        TextInput::make('announcement_text')->maxLength(255)->columnSpanFull(),
                        TextInput::make('operating_hours')->maxLength(160)->columnSpanFull(),
                    ]),

                Section::make('Footer & Social Accounts')
                    ->schema([
                        Textarea::make('footer_about')->rows(3)->maxLength(500),
                        TextInput::make('footer_copyright')->maxLength(180),
                        Repeater::make('social_links')
                            ->label('Footer Social Links')
                            ->schema([
                                Select::make('platform')
                                    ->options([
                                        'facebook' => 'Facebook',
                                        'instagram' => 'Instagram',
                                        'youtube' => 'YouTube',
                                        'linkedin' => 'LinkedIn',
                                        'whatsapp' => 'WhatsApp',
                                    ])
                                    ->required(),
                                TextInput::make('label')->required()->maxLength(60),
                                TextInput::make('url')->required()->maxLength(255),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public function save(
        SiteSettingsService $settings,
        HtmlSanitizerService $sanitizer,
        AuditLogger $audit,
    ): void {
        abort_unless(static::canAccess(), 403);

        try {
            $state = $this->form->getState();

            $settings->set('site_name', $sanitizer->sanitizePlainText($state['site_name'] ?? '', 'site_name', 120), 'general', 'string', true);
            $settings->set('site_tagline', $sanitizer->sanitizePlainText($state['site_tagline'] ?? '', 'site_tagline', 220), 'general', 'string', true);
            $settings->set('brand_logo_url', $sanitizer->sanitizeUrl($state['brand_logo_url'] ?? '', 'brand_logo_url'), 'appearance', 'string', true);
            $settings->set('theme_color_preset', (string) ($state['theme_color_preset'] ?? 'karakoram_emerald'), 'appearance', 'string', true);
            $settings->set('theme_typography_preset', (string) ($state['theme_typography_preset'] ?? 'modern_editorial'), 'appearance', 'string', true);
            $settings->set('announcement_enabled', (bool) ($state['announcement_enabled'] ?? false), 'general', 'boolean', true);
            $settings->set('announcement_text', $sanitizer->sanitizePlainText($state['announcement_text'] ?? '', 'announcement_text', 255), 'general', 'string', true);
            $settings->set('support_email', $sanitizer->sanitizePlainText($state['support_email'] ?? '', 'support_email', 160), 'general', 'string', true);
            $settings->set('operating_hours', $sanitizer->sanitizePlainText($state['operating_hours'] ?? '', 'operating_hours', 160), 'general', 'string', true);
            $settings->set('footer_about', $sanitizer->sanitizePlainText($state['footer_about'] ?? '', 'footer_about', 500), 'footer', 'string', true);
            $settings->set('footer_copyright', $sanitizer->sanitizePlainText($state['footer_copyright'] ?? '', 'footer_copyright', 180), 'footer', 'string', true);

            $cleanSocial = [];
            foreach ($state['social_links'] ?? [] as $row) {
                $url = $sanitizer->sanitizeUrl($row['url'] ?? '', 'social_links.url');
                if ($url !== '') {
                    $cleanSocial[] = [
                        'platform' => (string) ($row['platform'] ?? 'facebook'),
                        'label' => $sanitizer->sanitizePlainText($row['label'] ?? '', 'social_links.label', 60),
                        'url' => $url,
                    ];
                }
            }
            $settings->set('social_links', $cleanSocial, 'social', 'json', true);

            /** @var User $actor */
            $actor = auth()->user();
            $audit->record(
                actor: $actor,
                event: 'cms.site_settings_updated',
                summary: 'Updated global CMS site settings and visual theme presets',
                auditable: null,
                targetUser: null,
                beforeState: null,
                afterState: [
                    'site_name' => $state['site_name'] ?? null,
                    'theme_color_preset' => $state['theme_color_preset'] ?? null,
                ],
            );

            Notification::make()->title('Site settings saved and public cache refreshed.')->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }
}
