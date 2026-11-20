<?php

namespace App\Filament\Resources\HotelResource\Pages;

use App\Filament\Resources\HotelResource;
use App\Models\User;
use App\Modules\Hotels\Services\HotelCatalogService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateHotel extends CreateRecord
{
    protected static string $resource = HotelResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();

        return app(HotelCatalogService::class)->saveHotel($actor, null, $data);
    }
}
