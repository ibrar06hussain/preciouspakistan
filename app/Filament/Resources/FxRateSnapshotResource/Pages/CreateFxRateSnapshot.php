<?php

namespace App\Filament\Resources\FxRateSnapshotResource\Pages;

use App\Filament\Resources\FxRateSnapshotResource;
use App\Modules\Finance\Services\InvoiceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFxRateSnapshot extends CreateRecord
{
    protected static string $resource = FxRateSnapshotResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(InvoiceService::class)->recordFxRate(
            officer: auth()->user(),
            baseCurrency: (string) $data['base_currency'],
            quoteCurrency: (string) $data['quote_currency'],
            rate: (string) $data['rate'],
            source: (string) $data['source'],
            isManualOverride: (bool) ($data['is_manual_override'] ?? true),
            notes: $data['notes'] ?? null,
        );
    }
}
