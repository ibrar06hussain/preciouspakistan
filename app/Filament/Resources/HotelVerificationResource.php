<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HotelVerificationResource\Pages;
use App\Models\User;
use App\Modules\Hotels\Models\Hotel;
use App\Modules\Hotels\Models\HotelAvailabilityVerification;
use App\Modules\Hotels\Models\HotelRoomType;
use App\Modules\Hotels\Models\TourStayHotel;
use App\Modules\Hotels\Services\HotelVerificationService;
use App\Shared\Support\DisplayTimezone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;
use UnitEnum;

class HotelVerificationResource extends Resource
{
    protected static ?string $model = HotelAvailabilityVerification::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|UnitEnum|null $navigationGroup = 'Tour-Only Hotels';

    protected static ?string $navigationLabel = 'External Verifications';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_code')->badge()->color('gray')->searchable(),
                TextColumn::make('tour.code')->label('Tour')->searchable(),
                TextColumn::make('requestedHotel.name')->label('Requested Hotel')->searchable(),
                TextColumn::make('requestedRoomType.name')->label('Room'),
                TextColumn::make('check_in_date')->date()->sortable(),
                TextColumn::make('nights_count')->label('Nights'),
                TextColumn::make('rooms_count')->label('Rooms'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    HotelAvailabilityVerification::STATUS_VERIFIED_AVAILABLE,
                    HotelAvailabilityVerification::STATUS_CONFIRMED_ALTERNATIVE => 'success',
                    HotelAvailabilityVerification::STATUS_UNAVAILABLE_ALTERNATIVE_PROPOSED => 'warning',
                    HotelAvailabilityVerification::STATUS_REJECTED_UNAVAILABLE => 'danger',
                    default => 'gray',
                }),
                TextColumn::make('verification_method')->label('Method')->placeholder('—'),
                TextColumn::make('verified_at')
                    ->label('Verified (PKT)')
                    ->formatStateUsing(fn ($state): string => DisplayTimezone::format($state)),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    HotelAvailabilityVerification::STATUS_PENDING => 'Pending Verification',
                    HotelAvailabilityVerification::STATUS_VERIFIED_AVAILABLE => 'Verified Available',
                    HotelAvailabilityVerification::STATUS_UNAVAILABLE_ALTERNATIVE_PROPOSED => 'Alternative Proposed',
                    HotelAvailabilityVerification::STATUS_CONFIRMED_ALTERNATIVE => 'Confirmed Alternative',
                    HotelAvailabilityVerification::STATUS_REJECTED_UNAVAILABLE => 'Rejected Unavailable',
                ]),
            ])
            ->recordActions([
                Action::make('log_verification')
                    ->label('Log External Verification')
                    ->color('primary')
                    ->visible(fn (HotelAvailabilityVerification $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->form(fn (HotelAvailabilityVerification $record): array => [
                        Select::make('status')
                            ->options([
                                HotelAvailabilityVerification::STATUS_VERIFIED_AVAILABLE => 'Verified Available (Requested Hotel)',
                                HotelAvailabilityVerification::STATUS_UNAVAILABLE_ALTERNATIVE_PROPOSED => 'Unavailable — Propose Alternative',
                                HotelAvailabilityVerification::STATUS_CONFIRMED_ALTERNATIVE => 'Confirmed Alternative Hotel',
                                HotelAvailabilityVerification::STATUS_REJECTED_UNAVAILABLE => 'Rejected — Unavailable',
                            ])
                            ->required()
                            ->live(),
                        Select::make('verification_method')
                            ->options(HotelAvailabilityVerification::METHODS)
                            ->required(),
                        TextInput::make('external_reference')
                            ->label('External Reference (Contact Name / WhatsApp Msg / Email Ref)')
                            ->required()
                            ->maxLength(120),
                        Select::make('alternative_hotel_id')
                            ->label('Alternative Eligible Hotel')
                            ->options(fn (): array => TourStayHotel::query()
                                ->where('tour_stay_id', $record->tour_stay_id)
                                ->where('is_active', true)
                                ->with('hotel')
                                ->get()
                                ->pluck('hotel.name', 'hotel_id')
                                ->filter()
                                ->all())
                            ->live()
                            ->visible(fn (Get $get): bool => in_array($get('status'), [
                                HotelAvailabilityVerification::STATUS_UNAVAILABLE_ALTERNATIVE_PROPOSED,
                                HotelAvailabilityVerification::STATUS_CONFIRMED_ALTERNATIVE,
                            ], true)),
                        Select::make('alternative_room_type_id')
                            ->label('Alternative Room Type')
                            ->options(fn (Get $get): array => $get('alternative_hotel_id')
                                ? HotelRoomType::query()->where('hotel_id', $get('alternative_hotel_id'))->where('is_active', true)->pluck('name', 'id')->all()
                                : [])
                            ->visible(fn (Get $get): bool => filled($get('alternative_hotel_id'))),
                        Textarea::make('officer_notes')->rows(3),
                    ])
                    ->action(function (HotelAvailabilityVerification $record, array $data): void {
                        try {
                            /** @var User $officer */
                            $officer = auth()->user();
                            $altHotel = filled($data['alternative_hotel_id'] ?? null) ? Hotel::query()->find($data['alternative_hotel_id']) : null;
                            $altRoom = filled($data['alternative_room_type_id'] ?? null) ? HotelRoomType::query()->find($data['alternative_room_type_id']) : null;

                            app(HotelVerificationService::class)->recordExternalVerification(
                                officer: $officer,
                                verification: $record,
                                status: (string) $data['status'],
                                method: (string) $data['verification_method'],
                                externalReference: (string) $data['external_reference'],
                                officerNotes: $data['officer_notes'] ?? null,
                                alternativeHotel: $altHotel,
                                alternativeRoomType: $altRoom,
                            );

                            Notification::make()->title('External hotel verification logged.')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHotelVerifications::route('/'),
        ];
    }
}
