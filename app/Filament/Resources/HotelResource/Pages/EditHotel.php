<?php

namespace App\Filament\Resources\HotelResource\Pages;

use App\Filament\Resources\HotelResource;
use App\Models\User;
use App\Modules\Hotels\Models\Hotel;
use App\Modules\Hotels\Services\HotelCatalogService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class EditHotel extends EditRecord
{
    protected static string $resource = HotelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('copy_next_year_rates')
                ->label('Copy Rates to Next Year')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->form([
                    TextInput::make('source_year')->numeric()->required()->default(now()->year),
                    TextInput::make('target_year')->numeric()->required()->default(now()->year + 1),
                ])
                ->action(function (array $data): void {
                    try {
                        /** @var User $actor */
                        $actor = auth()->user();
                        /** @var Hotel $hotel */
                        $hotel = $this->getRecord();
                        $copied = app(HotelCatalogService::class)->copyRatesToYear(
                            $actor,
                            $hotel,
                            (int) $data['source_year'],
                            (int) $data['target_year']
                        );
                        Notification::make()->title("Copied {$copied} room rate(s) to {$data['target_year']}.")->success()->send();
                    } catch (Throwable $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Hotel $hotel */
        $hotel = $this->getRecord();
        $hotel->load('roomTypes.rates');

        $data['room_types'] = $hotel->roomTypes->map(fn ($rt): array => [
            'name' => $rt->name,
            'code' => $rt->code,
            'max_occupancy' => $rt->max_occupancy,
            'bed_configuration' => $rt->bed_configuration,
            'is_active' => (bool) $rt->is_active,
            'rates' => $rt->rates->map(fn ($r): array => [
                'rate_name' => $r->rate_name,
                'rate_tier' => $r->rate_tier,
                'valid_from' => $r->valid_from?->toDateString(),
                'valid_to' => $r->valid_to?->toDateString(),
                'priority' => $r->priority,
                'nightly_rate_pkr' => $r->nightly_rate_pkr,
                'nightly_rate_usd' => $r->nightly_rate_usd,
                'is_active' => (bool) $r->is_active,
            ])->all(),
        ])->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();
        /** @var Hotel $hotel */
        $hotel = $record;

        return app(HotelCatalogService::class)->saveHotel($actor, $hotel, $data);
    }
}
