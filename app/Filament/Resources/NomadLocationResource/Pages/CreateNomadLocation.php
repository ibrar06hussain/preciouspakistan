<?php

namespace App\Filament\Resources\NomadLocationResource\Pages;

use App\Filament\Resources\NomadLocationResource;
use App\Models\User;
use App\Modules\Nomads\Services\NomadCatalogService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateNomadLocation extends CreateRecord
{
    protected static string $resource = NomadLocationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();

        return app(NomadCatalogService::class)->saveLocation($actor, null, $data);
    }
}
