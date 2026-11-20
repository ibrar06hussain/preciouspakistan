<?php

namespace App\Filament\Resources\AttractionResource\Pages;

use App\Filament\Resources\AttractionResource;
use App\Models\User;
use App\Modules\Destinations\Models\Attraction;
use App\Modules\Destinations\Services\DestinationHierarchyService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAttraction extends EditRecord
{
    protected static string $resource = AttractionResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();
        /** @var Attraction $attraction */
        $attraction = $record;

        return app(DestinationHierarchyService::class)->saveAttraction($actor, $attraction, $data);
    }
}
