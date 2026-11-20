<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Finance\Models\CreditNote;
use App\Modules\Finance\Models\FxRateSnapshot;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\InvoiceEvent;
use App\Modules\Finance\Models\InvoiceItem;
use App\Modules\Finance\Models\InvoicePayment;
use App\Modules\Rbac\Services\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class InvoiceService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Record an auditable USD->PKR (or custom pair) FX snapshot with source & timestamp.
     *
     * @throws AuthorizationException
     */
    public function recordFxRate(
        User $officer,
        string $baseCurrency,
        string $quoteCurrency,
        string $rate,
        string $source,
        bool $isManualOverride = true,
        ?string $notes = null,
    ): FxRateSnapshot {
        if (! $officer->can('fx.manage')) {
            throw new AuthorizationException('You do not have permission to manage FX rates.');
        }

        $base = strtoupper(trim($baseCurrency));
        $quote = strtoupper(trim($quoteCurrency));
        if (! is_numeric($rate) || bccomp($rate, '0.000001', 6) <= 0) {
            throw new InvalidArgumentException('FX rate must be a positive decimal.');
        }

        $snapshot = FxRateSnapshot::query()->create([
            'base_currency' => $base,
            'quote_currency' => $quote,
            'rate' => number_format((float) $rate, 6, '.', ''),
            'source' => mb_substr(trim($source), 0, 120),
            'is_manual_override' => $isManualOverride,
            'notes' => $notes,
            'recorded_by' => $officer->id,
            'recorded_at' => now(),
        ]);

        $this->audit->record(
            actor: $officer,
            event: 'finance.fx_rate_recorded',
            summary: "Recorded FX {$base}/{$quote} = {$snapshot->rate} ({$snapshot->source})",
            auditable: $snapshot,
            afterState: ['pair' => "{$base}/{$quote}", 'rate' => (string) $snapshot->rate, 'source' => $snapshot->source],
        );

        return $snapshot;
    }

    /**
     * Create a draft invoice (PKR or USD) from a Booking's immutable pricing snapshot.
     * If invoice currency differs from booking currency, converts using an explicit FX snapshot (source + timestamp recorded).
     *
     * @throws AuthorizationException
     */
    public function createFromBooking(
        User $officer,
        Booking $booking,
        string $invoiceType = Invoice::TYPE_PROFORMA,
        ?string $targetCurrency = null,
        ?FxRateSnapshot $fxSnapshot = null,
        ?CarbonInterface $dueDate = null,
        ?string $paymentInstructions = null,
    ): Invoice {
        if (! $officer->can('invoices.create')) {
            throw new AuthorizationException('You do not have permission to create invoices.');
        }

        if (! in_array($invoiceType, [Invoice::TYPE_PROFORMA, Invoice::TYPE_FINAL], true)) {
            throw new InvalidArgumentException("Invalid invoice type [{$invoiceType}].");
        }

        $booking->loadMissing('items');
        $sourceCurrency = strtoupper($booking->currency);
        $currency = strtoupper(trim($targetCurrency ?? $sourceCurrency));

        if (! in_array($currency, ['PKR', 'USD'], true)) {
            throw new InvalidArgumentException("Unsupported invoice currency [{$currency}].");
        }

        $fxRateUsed = null;
        $fxSource = null;
        $fxRecordedAt = null;
        $fxSnapshotId = null;

        if ($currency !== $sourceCurrency) {
            $fxSnapshot ??= FxRateSnapshot::query()
                ->where('base_currency', 'USD')
                ->where('quote_currency', 'PKR')
                ->orderByDesc('recorded_at')
                ->first();

            if ($fxSnapshot === null) {
                throw new InvalidArgumentException('An explicit FX rate snapshot (USD/PKR) is required to convert invoice currency.');
            }

            $fxSnapshotId = $fxSnapshot->id;
            $fxRateUsed = (string) $fxSnapshot->rate;
            $fxSource = $fxSnapshot->source;
            $fxRecordedAt = $fxSnapshot->recorded_at;
        }

        $convert = function (string $amount) use ($sourceCurrency, $currency, $fxRateUsed): string {
            if ($sourceCurrency === $currency) {
                return number_format((float) $amount, 2, '.', '');
            }
            if ($sourceCurrency === 'PKR' && $currency === 'USD') {
                return bcdiv($amount, (string) $fxRateUsed, 2);
            }

            return bcmul($amount, (string) $fxRateUsed, 2);
        };

        return DB::transaction(function () use (
            $officer,
            $booking,
            $invoiceType,
            $currency,
            $fxSnapshotId,
            $fxRateUsed,
            $fxSource,
            $fxRecordedAt,
            $dueDate,
            $paymentInstructions,
            $convert,
        ): Invoice {
            $subtotal = '0.00';
            $itemRows = [];

            foreach ($booking->items as $item) {
                $unitConverted = $item->is_included ? '0.00' : $convert((string) $item->unit_price);
                $lineConverted = $item->is_included ? '0.00' : $convert((string) $item->line_total);
                $subtotal = bcadd($subtotal, $lineConverted, 2);

                $itemRows[] = [
                    'item_type' => $item->item_type,
                    'label' => $item->label,
                    'charge_unit' => $item->charge_unit,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitConverted,
                    'line_total' => $lineConverted,
                    'is_included' => (bool) $item->is_included,
                ];
            }

            $discount = $convert((string) $booking->discount_amount);
            if (bccomp($discount, $subtotal, 2) > 0) {
                $discount = $subtotal;
            }
            $taxable = bcsub($subtotal, $discount, 2);
            $taxRate = (string) $booking->tax_rate_percent;
            $taxTotal = bccomp($taxRate, '0.000', 3) > 0
                ? bcdiv(bcmul($taxable, $taxRate, 4), '100', 2)
                : '0.00';
            $totalAmount = bcadd($taxable, $taxTotal, 2);

            $invoice = Invoice::query()->create([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'invoice_type' => $invoiceType,
                'status' => Invoice::STATUS_DRAFT,
                'currency' => $currency,
                'fx_rate_snapshot_id' => $fxSnapshotId,
                'fx_rate_used' => $fxRateUsed,
                'fx_source' => $fxSource,
                'fx_recorded_at' => $fxRecordedAt,
                'customer_name' => $booking->customer_name,
                'customer_email' => $booking->customer_email,
                'customer_phone' => $booking->customer_phone,
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'tax_rate_percent' => $taxRate,
                'tax_total' => $taxTotal,
                'total_amount' => $totalAmount,
                'credit_note_total' => '0.00',
                'amount_paid' => '0.00',
                'balance_due' => $totalAmount,
                'due_date' => ($dueDate ?? now()->addDays(7))->toDateString(),
                'verification_token' => bin2hex(random_bytes(24)),
                'payment_instructions' => $paymentInstructions ?? 'Pay via official Precious Pakistan bank transfer. Include your invoice number as transfer reference.',
                'is_sample_content' => (bool) $booking->is_sample_content,
            ]);

            foreach ($itemRows as $row) {
                InvoiceItem::query()->create(array_merge(['invoice_id' => $invoice->id], $row));
            }

            InvoiceEvent::query()->create([
                'invoice_id' => $invoice->id,
                'actor_id' => $officer->id,
                'event_type' => 'invoice.draft_created',
                'summary' => "Draft {$invoiceType} invoice created from booking {$booking->booking_reference} ({$currency} {$totalAmount})",
            ]);

            return $invoice->fresh(['items', 'booking']);
        });
    }

    /**
     * Issue a draft invoice: assigns sequential invoice number, captures immutable snapshot, and locks financial fields permanently.
     *
     * @throws AuthorizationException
     */
    public function issueInvoice(User $officer, Invoice $invoice): Invoice
    {
        if (! $officer->can('invoices.issue')) {
            throw new AuthorizationException('You do not have permission to issue invoices.');
        }

        return DB::transaction(function () use ($officer, $invoice): Invoice {
            $locked = Invoice::query()->lockForUpdate()->with('items')->findOrFail($invoice->id);

            if ($locked->isIssued()) {
                return $locked;
            }

            if ($locked->status === Invoice::STATUS_VOID) {
                throw new RuntimeException('Cannot issue a voided invoice.');
            }

            $year = now()->format('Y');
            $prefix = "PP-INV-{$year}-";
            $lastNumber = Invoice::query()
                ->where('invoice_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('invoice_number')
                ->value('invoice_number');

            $nextSeq = $lastNumber !== null ? ((int) Str::afterLast($lastNumber, '-')) + 1 : 1;
            $invoiceNumber = $prefix.str_pad((string) $nextSeq, 5, '0', STR_PAD_LEFT);

            $snapshot = [
                'invoice_number' => $invoiceNumber,
                'invoice_type' => $locked->invoice_type,
                'issued_at_utc' => now('UTC')->toIso8601String(),
                'currency' => $locked->currency,
                'fx_rate_used' => $locked->fx_rate_used,
                'fx_source' => $locked->fx_source,
                'fx_recorded_at' => $locked->fx_recorded_at?->toIso8601String(),
                'customer' => [
                    'name' => $locked->customer_name,
                    'email' => $locked->customer_email,
                    'phone' => $locked->customer_phone,
                ],
                'totals' => [
                    'subtotal' => (string) $locked->subtotal,
                    'discount_total' => (string) $locked->discount_total,
                    'tax_rate_percent' => (string) $locked->tax_rate_percent,
                    'tax_total' => (string) $locked->tax_total,
                    'total_amount' => (string) $locked->total_amount,
                ],
                'items' => $locked->items->map(fn (InvoiceItem $i): array => [
                    'item_type' => $i->item_type,
                    'label' => $i->label,
                    'charge_unit' => $i->charge_unit,
                    'quantity' => $i->quantity,
                    'unit_price' => (string) $i->unit_price,
                    'line_total' => (string) $i->line_total,
                    'is_included' => (bool) $i->is_included,
                ])->all(),
            ];

            $locked->forceFill([
                'invoice_number' => $invoiceNumber,
                'status' => Invoice::STATUS_ISSUED,
                'issued_at' => now(),
                'issued_by' => $officer->id,
                'immutable_snapshot' => $snapshot,
            ])->save();

            InvoiceEvent::query()->create([
                'invoice_id' => $locked->id,
                'actor_id' => $officer->id,
                'event_type' => 'invoice.issued',
                'summary' => "Issued immutable invoice [{$invoiceNumber}] ({$locked->currency} {$locked->total_amount})",
            ]);

            $this->audit->record(
                actor: $officer,
                event: 'finance.invoice_issued',
                summary: "Issued invoice [{$invoiceNumber}] for {$locked->currency} {$locked->total_amount}",
                auditable: $locked,
                afterState: ['invoice_number' => $invoiceNumber, 'total_amount' => (string) $locked->total_amount, 'currency' => $locked->currency],
            );

            return $locked->fresh(['items', 'payments', 'creditNotes']);
        });
    }

    /**
     * Record a manual payment (bank transfer / cash / cheque), issue a receipt number, update balance & booking payment_status.
     *
     * @throws AuthorizationException
     */
    public function recordManualPayment(
        User $officer,
        Invoice $invoice,
        string $amount,
        string $paymentMethod = InvoicePayment::METHOD_BANK_TRANSFER,
        ?string $externalReference = null,
        ?string $notes = null,
    ): InvoicePayment {
        if (! $officer->can('payments.record')) {
            throw new AuthorizationException('You do not have permission to record payments.');
        }

        if (! is_numeric($amount) || bccomp($amount, '0.00', 2) <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($officer, $invoice, $amount, $paymentMethod, $externalReference, $notes): InvoicePayment {
            $locked = Invoice::query()->lockForUpdate()->with('booking')->findOrFail($invoice->id);

            if (! $locked->isIssued() || $locked->status === Invoice::STATUS_VOID) {
                throw new RuntimeException('Payments can only be recorded against active issued invoices.');
            }

            $normalizedAmount = number_format((float) $amount, 2, '.', '');
            if (bccomp($normalizedAmount, (string) $locked->balance_due, 2) > 0) {
                throw new InvalidArgumentException("Payment ({$normalizedAmount}) exceeds remaining invoice balance ({$locked->balance_due}).");
            }

            $year = now()->format('Y');
            $prefix = "PP-RCT-{$year}-";
            $last = InvoicePayment::query()->where('receipt_number', 'like', $prefix.'%')->lockForUpdate()->orderByDesc('receipt_number')->value('receipt_number');
            $seq = $last !== null ? ((int) Str::afterLast($last, '-')) + 1 : 1;
            $receiptNumber = $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);

            $payment = InvoicePayment::query()->create([
                'receipt_number' => $receiptNumber,
                'invoice_id' => $locked->id,
                'payment_method' => $paymentMethod,
                'currency' => $locked->currency,
                'amount' => $normalizedAmount,
                'external_reference' => $externalReference ? mb_substr(trim($externalReference), 0, 120) : null,
                'paid_at' => now(),
                'recorded_by' => $officer->id,
                'notes' => $notes,
            ]);

            $newAmountPaid = bcadd((string) $locked->amount_paid, $normalizedAmount, 2);
            $netDue = bcsub(bcsub((string) $locked->total_amount, (string) $locked->credit_note_total, 2), $newAmountPaid, 2);
            $newBalance = bccomp($netDue, '0.00', 2) > 0 ? $netDue : '0.00';
            $newInvoiceStatus = bccomp($newBalance, '0.00', 2) === 0 ? Invoice::STATUS_PAID : Invoice::STATUS_PARTIAL;

            $locked->forceFill([
                'amount_paid' => $newAmountPaid,
                'balance_due' => $newBalance,
                'status' => $newInvoiceStatus,
            ])->save();

            // Sync ONLY payment_status on booking (booking_status stays strictly independent!).
            if ($locked->booking !== null) {
                $locked->booking->forceFill([
                    'payment_status' => $newInvoiceStatus === Invoice::STATUS_PAID ? Booking::PAYMENT_PAID : Booking::PAYMENT_PARTIAL,
                ])->save();
            }

            InvoiceEvent::query()->create([
                'invoice_id' => $locked->id,
                'actor_id' => $officer->id,
                'event_type' => 'invoice.payment_recorded',
                'summary' => "Recorded {$paymentMethod} payment [{$receiptNumber}] of {$locked->currency} {$normalizedAmount}",
            ]);

            $this->audit->record(
                actor: $officer,
                event: 'finance.payment_recorded',
                summary: "Recorded payment [{$receiptNumber}] on invoice [{$locked->invoice_number}] ({$locked->currency} {$normalizedAmount})",
                auditable: $payment,
                afterState: ['receipt_number' => $receiptNumber, 'amount' => $normalizedAmount, 'invoice_status' => $newInvoiceStatus],
            );

            return $payment;
        });
    }

    /**
     * Issue a Credit Note against an issued invoice — adjusts balance cleanly without ever mutating original invoice lines/totals.
     *
     * @throws AuthorizationException
     */
    public function issueCreditNote(User $officer, Invoice $invoice, string $amount, string $reason): CreditNote
    {
        if (! $officer->can('invoices.issue')) {
            throw new AuthorizationException('You do not have permission to issue credit notes.');
        }

        $reason = trim($reason);
        if ($reason === '' || ! is_numeric($amount) || bccomp($amount, '0.00', 2) <= 0) {
            throw new InvalidArgumentException('Credit note requires a positive amount and a clear audit reason.');
        }

        return DB::transaction(function () use ($officer, $invoice, $amount, $reason): CreditNote {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if (! $locked->isIssued() || $locked->status === Invoice::STATUS_VOID) {
                throw new RuntimeException('Credit notes can only be issued against active issued invoices.');
            }

            $normalized = number_format((float) $amount, 2, '.', '');
            $maxCredit = bcsub((string) $locked->total_amount, (string) $locked->credit_note_total, 2);
            if (bccomp($normalized, $maxCredit, 2) > 0) {
                throw new InvalidArgumentException("Credit note ({$normalized}) exceeds remaining creditable invoice total ({$maxCredit}).");
            }

            $year = now()->format('Y');
            $prefix = "PP-CN-{$year}-";
            $last = CreditNote::query()->where('credit_note_number', 'like', $prefix.'%')->lockForUpdate()->orderByDesc('credit_note_number')->value('credit_note_number');
            $seq = $last !== null ? ((int) Str::afterLast($last, '-')) + 1 : 1;
            $cnNumber = $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);

            $creditNote = CreditNote::query()->create([
                'credit_note_number' => $cnNumber,
                'invoice_id' => $locked->id,
                'currency' => $locked->currency,
                'amount' => $normalized,
                'reason' => mb_substr($reason, 0, 255),
                'issued_by' => $officer->id,
                'issued_at' => now(),
            ]);

            $newCreditTotal = bcadd((string) $locked->credit_note_total, $normalized, 2);
            $netDue = bcsub(bcsub((string) $locked->total_amount, $newCreditTotal, 2), (string) $locked->amount_paid, 2);
            $newBalance = bccomp($netDue, '0.00', 2) > 0 ? $netDue : '0.00';

            $locked->forceFill([
                'credit_note_total' => $newCreditTotal,
                'balance_due' => $newBalance,
                'status' => bccomp($newBalance, '0.00', 2) === 0 ? Invoice::STATUS_PAID : $locked->status,
            ])->save();

            InvoiceEvent::query()->create([
                'invoice_id' => $locked->id,
                'actor_id' => $officer->id,
                'event_type' => 'invoice.credit_note_issued',
                'summary' => "Issued credit note [{$cnNumber}] for {$locked->currency} {$normalized}: {$reason}",
            ]);

            $this->audit->record(
                actor: $officer,
                event: 'finance.credit_note_issued',
                summary: "Issued credit note [{$cnNumber}] on [{$locked->invoice_number}]",
                auditable: $creditNote,
                afterState: ['credit_note_number' => $cnNumber, 'amount' => $normalized, 'reason' => $reason],
            );

            return $creditNote;
        });
    }

    /**
     * Void an invoice with mandatory reason and audit log (requires `invoices.void`).
     *
     * @throws AuthorizationException
     */
    public function voidInvoice(User $officer, Invoice $invoice, string $reason): Invoice
    {
        if (! $officer->can('invoices.void')) {
            throw new AuthorizationException('You do not have permission to void invoices.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A void reason is required.');
        }

        return DB::transaction(function () use ($officer, $invoice, $reason): Invoice {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            $locked->forceFill([
                'status' => Invoice::STATUS_VOID,
                'voided_at' => now(),
                'voided_by' => $officer->id,
                'void_reason' => mb_substr($reason, 0, 255),
                'balance_due' => '0.00',
            ])->save();

            InvoiceEvent::query()->create([
                'invoice_id' => $locked->id,
                'actor_id' => $officer->id,
                'event_type' => 'invoice.voided',
                'summary' => "Voided invoice [{$locked->invoice_number}]: {$reason}",
            ]);

            $this->audit->record(
                actor: $officer,
                event: 'finance.invoice_voided',
                summary: "Voided invoice [{$locked->invoice_number}]",
                auditable: $locked,
                afterState: ['status' => Invoice::STATUS_VOID, 'void_reason' => $reason],
            );

            return $locked->fresh();
        });
    }
}
