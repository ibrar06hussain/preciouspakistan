<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Filament\Resources\DistrictResource;
use App\Models\User;
use App\Modules\Destinations\Models\District;
use App\Modules\Destinations\Services\DestinationHierarchyService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDistrict extends EditRecord
{
    protected static string $resource = DistrictResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();
        /** @var District $district */
        $district = $record;

        return app(DestinationHierarchyService::class)->saveDistrict($actor, $district, $data);
    }
}
