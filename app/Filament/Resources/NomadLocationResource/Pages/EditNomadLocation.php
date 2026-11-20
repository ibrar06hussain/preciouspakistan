<?php

namespace App\Filament\Resources\NomadLocationResource\Pages;

use App\Filament\Resources\NomadLocationResource;
use App\Models\User;
use App\Modules\Nomads\Models\NomadLocation;
use App\Modules\Nomads\Services\NomadCatalogService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditNomadLocation extends EditRecord
{
    protected static string $resource = NomadLocationResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var NomadLocation $loc */
        $loc = $this->getRecord();
        $loc->load('facilities.rates');

        $data['facilities'] = $loc->facilities->map(fn ($f): array => [
            'name' => $f->name,
            'code' => $f->code,
            'facility_type' => $f->facility_type,
            'seat_capacity' => $f->seat_capacity,
            'allows_daily' => (bool) $f->allows_daily,
            'allows_hourly' => (bool) $f->allows_hourly,
            'rates' => $f->rates->map(fn ($r): array => [
                'rate_name' => $r->rate_name,
                'valid_from' => $r->valid_from?->toDateString(),
                'valid_to' => $r->valid_to?->toDateString(),
                'daily_rate_pkr' => $r->daily_rate_pkr,
                'daily_rate_usd' => $r->daily_rate_usd,
                'hourly_rate_pkr' => $r->hourly_rate_pkr,
                'hourly_rate_usd' => $r->hourly_rate_usd,
            ])->all(),
        ])->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();
        /** @var NomadLocation $loc */
        $loc = $record;

        return app(NomadCatalogService::class)->saveLocation($actor, $loc, $data);
    }
}
