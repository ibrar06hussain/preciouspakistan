<?php

namespace App\Filament\Resources\UrlRedirectResource\Pages;

use App\Filament\Resources\UrlRedirectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUrlRedirects extends ListRecords
{
    protected static string $resource = UrlRedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
