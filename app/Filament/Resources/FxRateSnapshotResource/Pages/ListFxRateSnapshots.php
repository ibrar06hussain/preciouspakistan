<?php

namespace App\Filament\Resources\FxRateSnapshotResource\Pages;

use App\Filament\Resources\FxRateSnapshotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFxRateSnapshots extends ListRecords
{
    protected static string $resource = FxRateSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
