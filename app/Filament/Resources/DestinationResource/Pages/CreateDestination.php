<?php

namespace App\Filament\Resources\DestinationResource\Pages;

use App\Filament\Resources\DestinationResource;
use App\Models\User;
use App\Modules\Destinations\Services\DestinationHierarchyService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDestination extends CreateRecord
{
    protected static string $resource = DestinationResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();

        return app(DestinationHierarchyService::class)->saveDestination($actor, null, $data);
    }
}
