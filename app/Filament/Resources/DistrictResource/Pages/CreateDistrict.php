<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Filament\Resources\DistrictResource;
use App\Models\User;
use App\Modules\Destinations\Services\DestinationHierarchyService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDistrict extends CreateRecord
{
    protected static string $resource = DistrictResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();

        return app(DestinationHierarchyService::class)->saveDistrict($actor, null, $data);
    }
}
