<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\RegionResource;
use App\Models\User;
use App\Modules\Destinations\Models\Region;
use App\Modules\Destinations\Services\DestinationHierarchyService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditRegion extends EditRecord
{
    protected static string $resource = RegionResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();
        /** @var Region $region */
        $region = $record;

        return app(DestinationHierarchyService::class)->saveRegion($actor, $region, $data);
    }
}
