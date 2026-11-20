<?php

namespace App\Filament\Pages;

use App\Modules\Identity\Contracts\MfaProvider;
use App\Shared\Support\DisplayTimezone;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();

        return [
            'staffUser' => $user,
            'roles' => $user ? $user->getRoleNames()->all() : [],
            'displayNow' => DisplayTimezone::format(now()),
            'displayTimezone' => DisplayTimezone::timezone(),
            'mfaConfigured' => app(MfaProvider::class)->isConfigured(),
        ];
    }
}
