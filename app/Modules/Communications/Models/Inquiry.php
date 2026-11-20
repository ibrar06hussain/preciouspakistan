<?php

namespace App\Modules\Communications\Models;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Tours\Models\Tour;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_RESOLVED => 'Resolved',
        self::STATUS_ARCHIVED => 'Archived',
    ];

    protected $fillable = [
        'reference_code',
        'source_page',
        'source_channel',
        'user_id',
        'tour_id',
        'booking_id',
        'name',
        'email',
        'phone',
        'preferred_dates',
        'message',
        'consent_accepted',
        'consent_recorded_at',
        'retention_until',
        'status',
        'assigned_to',
        'ip_hash',
        'staff_notes',
    ];

    protected function casts(): array
    {
        return [
            'consent_accepted' => 'boolean',
            'consent_recorded_at' => 'datetime',
            'retention_until' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
