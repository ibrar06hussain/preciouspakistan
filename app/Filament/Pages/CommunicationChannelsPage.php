<?php

namespace App\Filament\Pages;

use App\Modules\Communications\Models\CommunicationChannelConfig;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use UnitEnum;

class CommunicationChannelsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|UnitEnum|null $navigationGroup = 'Communications';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Meta API & Channels';

    protected static ?string $title = 'Connected Messaging Channels (Disabled Until Authorized)';

    protected string $view = 'filament.pages.communication-channels';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('social.manage');
    }

    /**
     * @return array{channels: Collection<int, CommunicationChannelConfig>}
     */
    protected function getViewData(): array
    {
        return [
            'channels' => CommunicationChannelConfig::query()->orderBy('id')->get(),
        ];
    }
}
