<?php

namespace App\Filament\Resources\NomadLocationResource\Pages;

use App\Filament\Resources\NomadLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNomadLocations extends ListRecords
{
    protected static string $resource = NomadLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Add Nomad Workspace')];
    }
}
