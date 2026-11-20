<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Invoice extends Model
{
    public const TYPE_PROFORMA = 'proforma';

    public const TYPE_FINAL = 'final';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_VOID = 'void';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_ISSUED => 'Issued',
        self::STATUS_PARTIAL => 'Partially Paid',
        self::STATUS_PAID => 'Paid',
        self::STATUS_OVERDUE => 'Overdue',
        self::STATUS_VOID => 'Voided',
    ];

    /**
     * Financial/identity columns locked once an invoice has been issued.
     *
     * @var list<string>
     */
    public const IMMUTABLE_AFTER_ISSUE_COLUMNS = [
        'invoice_number',
        'invoice_type',
        'currency',
        'fx_rate_snapshot_id',
        'fx_rate_used',
        'fx_source',
        'fx_recorded_at',
        'customer_name',
        'customer_email',
        'subtotal',
        'discount_total',
        'tax_rate_percent',
        'tax_total',
        'total_amount',
        'immutable_snapshot',
        'issued_at',
    ];

    protected $fillable = [
        'invoice_number',
        'booking_id',
        'user_id',
        'invoice_type',
        'status',
        'currency',
        'fx_rate_snapshot_id',
        'fx_rate_used',
        'fx_source',
        'fx_recorded_at',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'subtotal',
        'discount_total',
        'tax_rate_percent',
        'tax_total',
        'total_amount',
        'credit_note_total',
        'amount_paid',
        'balance_due',
        'due_date',
        'issued_at',
        'issued_by',
        'voided_at',
        'voided_by',
        'void_reason',
        'verification_token',
        'immutable_snapshot',
        'revision_of_invoice_id',
        'payment_instructions',
        'notes',
        'is_sample_content',
    ];

    protected function casts(): array
    {
        return [
            'fx_rate_used' => 'decimal:6',
            'fx_recorded_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_rate_percent' => 'decimal:3',
            'tax_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'credit_note_total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'due_date' => 'date',
            'issued_at' => 'datetime',
            'voided_at' => 'datetime',
            'immutable_snapshot' => 'array',
            'is_sample_content' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Invoice $invoice): void {
            if ($invoice->getOriginal('issued_at') !== null) {
                foreach (self::IMMUTABLE_AFTER_ISSUE_COLUMNS as $column) {
                    if ($invoice->isDirty($column)) {
                        throw new LogicException(
                            "Issued invoice [{$invoice->getOriginal('invoice_number')}] is immutable and cannot modify [{$column}]. Issue a credit note or void & create a revised invoice instead."
                        );
                    }
                }
            }
        });

        static::deleting(function (Invoice $invoice): void {
            if ($invoice->issued_at !== null) {
                throw new LogicException("Issued invoice [{$invoice->invoice_number}] cannot be deleted; void it with audit trail instead.");
            }
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fxRateSnapshot(): BelongsTo
    {
        return $this->belongsTo(FxRateSnapshot::class, 'fx_rate_snapshot_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id')->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class, 'invoice_id')->orderByDesc('paid_at');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class, 'invoice_id')->orderByDesc('issued_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(InvoiceEvent::class, 'invoice_id')->orderByDesc('id');
    }

    public function isIssued(): bool
    {
        return $this->issued_at !== null;
    }
}
