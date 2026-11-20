<?php

namespace App\Filament\Resources\AttractionResource\Pages;

use App\Filament\Resources\AttractionResource;
use App\Models\User;
use App\Modules\Destinations\Services\DestinationHierarchyService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAttraction extends CreateRecord
{
    protected static string $resource = AttractionResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();

        return app(DestinationHierarchyService::class)->saveAttraction($actor, null, $data);
    }
}
