<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingWorkflowService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use RuntimeException;
use UnitEnum;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & Finance';

    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Booking Overview')
                ->columns(3)
                ->schema([
                    TextEntry::make('booking_reference')->label('Reference'),
                    TextEntry::make('booking_status')->badge(),
                    TextEntry::make('payment_status')->badge(),
                    TextEntry::make('tour.title')->label('Tour'),
                    TextEntry::make('customer_name')->label('Customer'),
                    TextEntry::make('customer_email')->label('Email'),
                    TextEntry::make('customer_phone')->label('Phone'),
                    TextEntry::make('start_date')->date('d M Y'),
                    TextEntry::make('end_date')->date('d M Y'),
                    TextEntry::make('guests_count'),
                    TextEntry::make('currency'),
                    TextEntry::make('total_amount')->label('Total'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_reference')->searchable()->sortable()->copyable(),
                TextColumn::make('tour.title')->searchable()->limit(28),
                TextColumn::make('customer_name')->searchable(),
                TextColumn::make('start_date')->date('d M Y')->sortable(),
                TextColumn::make('booking_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Booking::STATUS_CONFIRMED => 'success',
                        Booking::STATUS_PENDING_VERIFICATION, Booking::STATUS_AWAITING_PAYMENT => 'warning',
                        Booking::STATUS_CANCELLED, Booking::STATUS_REFUND_REQUIRED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Booking::PAYMENT_PAID => 'success',
                        Booking::PAYMENT_PARTIAL => 'warning',
                        Booking::PAYMENT_REFUND_PENDING, Booking::PAYMENT_REFUNDED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total_amount')
                    ->formatStateUsing(fn ($state, Booking $record): string => "{$record->currency} ".number_format((float) $state, 2)),
            ])
            ->filters([
                SelectFilter::make('booking_status')->options(Booking::BOOKING_STATUSES),
                SelectFilter::make('payment_status')->options(Booking::PAYMENT_STATUSES),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('confirm')
                    ->label('Confirm Booking')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Booking $record): bool => auth()->user()?->can('bookings.confirm') && $record->booking_status !== Booking::STATUS_CONFIRMED && $record->booking_status !== Booking::STATUS_CANCELLED)
                    ->form([
                        Textarea::make('officer_notes')->label('Confirmation Notes')->rows(2),
                    ])
                    ->action(function (Booking $record, array $data): void {
                        try {
                            app(BookingWorkflowService::class)->confirmBooking(auth()->user(), $record, $data['officer_notes'] ?? null);
                            Notification::make()->title("Booking {$record->booking_reference} confirmed.")->success()->send();
                        } catch (RuntimeException $e) {
                            Notification::make()->title('Cannot Confirm Booking')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('adjust_discount')
                    ->label('Adjust Discount')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn (): bool => (bool) auth()->user()?->can('bookings.update'))
                    ->form([
                        TextInput::make('discount_amount')->numeric()->required(),
                        TextInput::make('reason')->required()->maxLength(200),
                    ])
                    ->action(function (Booking $record, array $data): void {
                        app(BookingWorkflowService::class)->adjustDiscount(auth()->user(), $record, (string) $data['discount_amount'], (string) $data['reason']);
                        Notification::make()->title('Booking discount adjusted.')->success()->send();
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (Booking $record): bool => auth()->user()?->can('bookings.cancel') && $record->booking_status !== Booking::STATUS_CANCELLED)
                    ->form([
                        TextInput::make('reason')->required()->maxLength(250),
                    ])
                    ->action(function (Booking $record, array $data): void {
                        app(BookingWorkflowService::class)->cancelBooking(auth()->user(), $record, (string) $data['reason']);
                        Notification::make()->title('Booking cancelled and holds released.')->warning()->send();
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'view' => Pages\ViewBooking::route('/{record}'),
        ];
    }
}
