<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use App\Models\User;
use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Services\TourCatalogService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class EditTour extends EditRecord
{
    protected static string $resource = TourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_public')
                ->label('View Public Page')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->visible(fn (): bool => $this->getRecord()->isPublished())
                ->url(fn (): string => $this->getRecord()->publicUrl())
                ->openUrlInNewTab(),

            Action::make('publish')
                ->label('Publish Tour')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => ! $this->getRecord()->isPublished() && (auth()->user()?->can('publish', $this->getRecord()) ?? false))
                ->action(function (): void {
                    try {
                        /** @var User $actor */
                        $actor = auth()->user();
                        /** @var Tour $tour */
                        $tour = $this->getRecord();
                        app(TourCatalogService::class)->publishTour($actor, $tour);
                        Notification::make()->title('Tour published successfully.')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (Throwable $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),

            Action::make('move_draft')
                ->label('Move to Draft')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status !== Tour::STATUS_DRAFT && (auth()->user()?->can('update', $this->getRecord()) ?? false))
                ->action(function (): void {
                    /** @var User $actor */
                    $actor = auth()->user();
                    /** @var Tour $tour */
                    $tour = $this->getRecord();
                    app(TourCatalogService::class)->moveToDraft($actor, $tour);
                    Notification::make()->title('Tour moved to draft.')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            Action::make('archive')
                ->label('Archive')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status !== Tour::STATUS_ARCHIVED && (auth()->user()?->can('publish', $this->getRecord()) ?? false))
                ->action(function (): void {
                    /** @var User $actor */
                    $actor = auth()->user();
                    /** @var Tour $tour */
                    $tour = $this->getRecord();
                    app(TourCatalogService::class)->archiveTour($actor, $tour);
                    Notification::make()->title('Tour archived.')->success()->send();
                    $this->refreshFormData(['status']);
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Tour $tour */
        $tour = $this->getRecord();
        $tour->load(['destinations', 'itineraryDays', 'stays', 'departures']);

        $data['destination_ids'] = $tour->destinations->pluck('id')->all();
        $data['itinerary_days'] = $tour->itineraryDays->map(fn ($d): array => [
            'day_number' => $d->day_number,
            'title' => $d->title,
            'destination_id' => $d->destination_id,
            'meals_included' => $d->meals_included,
            'altitude_meters' => $d->altitude_meters,
            'travel_distance_km' => $d->travel_distance_km,
            'description_html' => $d->description_html,
        ])->all();
        $data['stays'] = $tour->stays->map(fn ($s): array => [
            'destination_id' => $s->destination_id,
            'check_in_day' => $s->check_in_day,
            'nights_count' => $s->nights_count,
            'notes' => $s->notes,
        ])->all();
        $data['departures'] = $tour->departures->map(fn ($dep): array => [
            'departure_code' => $dep->departure_code,
            'start_date' => $dep->start_date?->toDateString(),
            'end_date' => $dep->end_date?->toDateString(),
            'capacity' => $dep->capacity,
            'status' => $dep->status,
            'price_override_pkr' => $dep->price_override_pkr,
            'price_override_usd' => $dep->price_override_usd,
            'notes' => $dep->notes,
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
        /** @var Tour $tour */
        $tour = $record;

        return app(TourCatalogService::class)->saveTour($actor, $tour, $data);
    }
}
