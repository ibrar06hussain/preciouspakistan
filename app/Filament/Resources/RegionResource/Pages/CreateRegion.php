<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\RegionResource;
use App\Models\User;
use App\Modules\Destinations\Services\DestinationHierarchyService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRegion extends CreateRecord
{
    protected static string $resource = RegionResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();

        return app(DestinationHierarchyService::class)->saveRegion($actor, null, $data);
    }
}
