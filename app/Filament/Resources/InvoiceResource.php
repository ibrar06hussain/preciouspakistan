<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\InvoicePayment;
use App\Modules\Finance\Services\InvoiceService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & Finance';

    protected static ?int $navigationSort = 20;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Invoice Details')
                ->columns(3)
                ->schema([
                    TextEntry::make('invoice_number')->placeholder('Draft'),
                    TextEntry::make('invoice_type')->badge(),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('customer_name'),
                    TextEntry::make('customer_email'),
                    TextEntry::make('currency'),
                    TextEntry::make('total_amount'),
                    TextEntry::make('amount_paid'),
                    TextEntry::make('balance_due'),
                    TextEntry::make('fx_rate_used')->placeholder('N/A'),
                    TextEntry::make('fx_source')->placeholder('N/A'),
                    TextEntry::make('issued_at')->dateTime('d M Y H:i')->placeholder('Unissued'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->placeholder('DRAFT')->searchable()->sortable()->copyable(),
                TextColumn::make('booking.booking_reference')->label('Booking')->searchable(),
                TextColumn::make('customer_name')->searchable(),
                TextColumn::make('invoice_type')->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Invoice::STATUS_PAID => 'success',
                        Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIAL => 'warning',
                        Invoice::STATUS_VOID, Invoice::STATUS_OVERDUE => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total_amount')->formatStateUsing(fn ($state, Invoice $r): string => "{$r->currency} ".number_format((float) $state, 2)),
                TextColumn::make('balance_due')->formatStateUsing(fn ($state, Invoice $r): string => "{$r->currency} ".number_format((float) $state, 2)),
            ])
            ->filters([
                SelectFilter::make('status')->options(Invoice::STATUSES),
                SelectFilter::make('currency')->options(['PKR' => 'PKR', 'USD' => 'USD']),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Invoice $record): string => route('invoices.pdf', ['invoice' => $record->id]))
                    ->openUrlInNewTab(),
                Action::make('issue')
                    ->label('Issue')
                    ->color('success')
                    ->icon('heroicon-o-check-badge')
                    ->visible(fn (Invoice $r): bool => auth()->user()?->can('invoices.issue') && ! $r->isIssued() && $r->status !== Invoice::STATUS_VOID)
                    ->action(function (Invoice $record): void {
                        $issued = app(InvoiceService::class)->issueInvoice(auth()->user(), $record);
                        Notification::make()->title("Issued {$issued->invoice_number}")->success()->send();
                    }),
                Action::make('record_payment')
                    ->label('Record Payment')
                    ->color('primary')
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn (Invoice $r): bool => auth()->user()?->can('payments.record') && $r->isIssued() && (float) $r->balance_due > 0 && $r->status !== Invoice::STATUS_VOID)
                    ->form([
                        TextInput::make('amount')->numeric()->required(),
                        Select::make('payment_method')->options([
                            InvoicePayment::METHOD_BANK_TRANSFER => 'Bank Transfer',
                            InvoicePayment::METHOD_CASH => 'Cash',
                            InvoicePayment::METHOD_CHEQUE => 'Cheque',
                        ])->default(InvoicePayment::METHOD_BANK_TRANSFER)->required(),
                        TextInput::make('external_reference')->label('Bank / Transfer Ref')->maxLength(120),
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        $receipt = app(InvoiceService::class)->recordManualPayment(
                            auth()->user(),
                            $record,
                            (string) $data['amount'],
                            (string) $data['payment_method'],
                            $data['external_reference'] ?? null,
                        );
                        Notification::make()->title("Receipt {$receipt->receipt_number} recorded.")->success()->send();
                    }),
                Action::make('credit_note')
                    ->label('Credit Note')
                    ->color('warning')
                    ->icon('heroicon-o-receipt-refund')
                    ->visible(fn (Invoice $r): bool => auth()->user()?->can('invoices.issue') && $r->isIssued() && $r->status !== Invoice::STATUS_VOID)
                    ->form([
                        TextInput::make('amount')->numeric()->required(),
                        TextInput::make('reason')->required()->maxLength(250),
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        $cn = app(InvoiceService::class)->issueCreditNote(auth()->user(), $record, (string) $data['amount'], (string) $data['reason']);
                        Notification::make()->title("Credit Note {$cn->credit_note_number} issued.")->success()->send();
                    }),
                Action::make('void')
                    ->label('Void')
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol')
                    ->visible(fn (Invoice $r): bool => auth()->user()?->can('invoices.void') && $r->status !== Invoice::STATUS_VOID)
                    ->form([
                        TextInput::make('reason')->required()->maxLength(250),
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        app(InvoiceService::class)->voidInvoice(auth()->user(), $record, (string) $data['reason']);
                        Notification::make()->title('Invoice voided.')->warning()->send();
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }
}
