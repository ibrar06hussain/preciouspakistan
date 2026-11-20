<?php

namespace App\Modules\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItem extends Model
{
    public const TYPE_TOUR_BASE = 'tour_base';

    public const TYPE_VEHICLE = 'vehicle';

    public const TYPE_DRIVER_INCLUDED = 'driver_included';

    public const TYPE_ROUTE_ADJUSTMENT = 'route_adjustment';

    public const TYPE_HOTEL_STAY = 'hotel_stay';

    public const TYPE_NOMAD_WORKSPACE = 'nomad_workspace';

    public const TYPE_EXTRA = 'extra';

    public const TYPE_DISCOUNT = 'discount';

    public const TYPE_TAX = 'tax';

    protected $fillable = [
        'booking_id',
        'item_type',
        'reference_type',
        'reference_id',
        'label',
        'charge_unit',
        'quantity',
        'unit_price',
        'line_total',
        'is_included',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'is_included' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
