<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_CASH = 'cash';

    public const METHOD_CHEQUE = 'cheque';

    protected $fillable = [
        'receipt_number',
        'invoice_id',
        'payment_method',
        'currency',
        'amount',
        'external_reference',
        'paid_at',
        'recorded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
