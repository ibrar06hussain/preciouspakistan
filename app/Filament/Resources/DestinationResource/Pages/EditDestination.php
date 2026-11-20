<?php

namespace App\Filament\Resources\DestinationResource\Pages;

use App\Filament\Resources\DestinationResource;
use App\Models\User;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Services\DestinationHierarchyService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDestination extends EditRecord
{
    protected static string $resource = DestinationResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();
        /** @var Destination $destination */
        $destination = $record;

        return app(DestinationHierarchyService::class)->saveDestination($actor, $destination, $data);
    }
}
