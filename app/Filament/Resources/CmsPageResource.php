<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CmsPageResource\Pages;
use App\Modules\Cms\Models\CmsPage;
use App\Modules\Cms\Support\ComponentRegistry;
use App\Modules\Cms\Support\ThemePresets;
use App\Shared\Support\DisplayTimezone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class CmsPageResource extends Resource
{
    protected static ?string $model = CmsPage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'CMS & Content';

    protected static ?string $navigationLabel = 'Pages & Visual Builder';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Page Identity & SEO')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(180),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(191)
                        ->unique(ignoreRecord: true)
                        ->helperText('URL path slug (e.g. about, contact, faqs). Reserved app slugs are rejected.'),
                    Toggle::make('is_homepage')
                        ->label('Set as Site Homepage'),
                    Toggle::make('noindex')
                        ->label('Noindex (Hide from search engines)'),
                    TextInput::make('seo_title')
                        ->label('SEO Title')
                        ->maxLength(180),
                    TextInput::make('canonical_url')
                        ->label('Canonical URL (Optional)')
                        ->maxLength(255),
                    Textarea::make('seo_description')
                        ->label('SEO Meta Description')
                        ->rows(2)
                        ->maxLength(320)
                        ->columnSpanFull(),
                ]),

            Section::make('Visual Section Builder (Draft Working Copy)')
                ->description('Add, remove, reorder, clone, hide, and style safe schema-validated sections. Draft saves never alter the live published page until published.')
                ->schema([
                    Repeater::make('sections')
                        ->label('Page Sections')
                        ->reorderable()
                        ->cloneable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => (ComponentRegistry::COMPONENT_TYPES[$state['component_type'] ?? ''] ?? 'New Section')
                            .(($state['is_visible'] ?? true) ? '' : ' [Hidden]'))
                        ->schema([
                            Select::make('component_type')
                                ->label('Component Type')
                                ->options(ComponentRegistry::options())
                                ->required()
                                ->reactive(),
                            Select::make('style_preset')
                                ->label('Section Style')
                                ->options(ThemePresets::sectionStylePresets())
                                ->default('default')
                                ->required(),
                            Select::make('device_visibility')
                                ->label('Device Visibility')
                                ->options(ThemePresets::deviceVisibilityOptions())
                                ->default('all')
                                ->required(),
                            Toggle::make('is_visible')
                                ->label('Visible')
                                ->default(true),
                            TextInput::make('config.eyebrow')
                                ->label('Eyebrow / Kicker')
                                ->maxLength(80),
                            TextInput::make('config.badge')
                                ->label('Badge Text')
                                ->maxLength(120),
                            TextInput::make('config.heading')
                                ->label('Heading')
                                ->maxLength(220),
                            Textarea::make('config.subheading')
                                ->label('Subheading / Intro')
                                ->rows(2)
                                ->maxLength(600),
                            Textarea::make('config.subtitle')
                                ->label('Subtitle')
                                ->rows(2)
                                ->maxLength(400),
                            Textarea::make('config.body')
                                ->label('Plain Body Copy')
                                ->rows(3),
                            Textarea::make('config.body_html')
                                ->label('Rich HTML Content (Sanitized Server-Side)')
                                ->rows(5)
                                ->helperText('Safe HTML tags allowed (<p>, <h3>, <ul>, <li>, <strong>, <a>). PHP, Blade, scripts, and unsafe URLs are rejected.'),
                            TextInput::make('config.primary_cta_label')
                                ->label('Primary CTA Label')
                                ->maxLength(80),
                            TextInput::make('config.primary_cta_href')
                                ->label('Primary CTA URL')
                                ->maxLength(255),
                            TextInput::make('config.secondary_cta_label')
                                ->label('Secondary CTA Label')
                                ->maxLength(80),
                            TextInput::make('config.secondary_cta_href')
                                ->label('Secondary CTA URL')
                                ->maxLength(255),
                            KeyValue::make('config_extra_notice')
                                ->label('Advanced JSON Notice')
                                ->helperText('Structured lists (cards, FAQs, regions, tours, fleet, markers) can be edited via JSON below.')
                                ->hidden(),
                            Textarea::make('config_json')
                                ->label('Structured Section Config (JSON — Optional Advanced Override)')
                                ->rows(6)
                                ->helperText('Validated strictly against ComponentRegistry schema on save. Leave untouched for simple text edits.')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->formatStateUsing(fn (string $state, CmsPage $record): string => $record->is_homepage ? '/ ('.$state.')' : '/'.$state),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        CmsPage::STATUS_PUBLISHED => 'success',
                        CmsPage::STATUS_SCHEDULED => 'warning',
                        default => 'gray',
                    }),
                IconColumn::make('is_homepage')
                    ->label('Home')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('Published (PKT)')
                    ->formatStateUsing(fn ($state): string => DisplayTimezone::format($state))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated (PKT)')
                    ->formatStateUsing(fn ($state): string => DisplayTimezone::format($state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        CmsPage::STATUS_DRAFT => 'Draft',
                        CmsPage::STATUS_SCHEDULED => 'Scheduled',
                        CmsPage::STATUS_PUBLISHED => 'Published',
                        CmsPage::STATUS_ARCHIVED => 'Archived',
                    ]),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (CmsPage $record): string => route('cms.pages.preview', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCmsPages::route('/'),
            'create' => Pages\CreateCmsPage::route('/create'),
            'edit' => Pages\EditCmsPage::route('/{record}/edit'),
        ];
    }
}
