<?php

namespace App\Modules\Rbac\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'event',
        'auditable_type',
        'auditable_id',
        'target_user_id',
        'summary',
        'before_state',
        'after_state',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before_state' => 'array',
            'after_state' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Audit logs are immutable and cannot be modified.');
        });

        static::deleting(function (): never {
            throw new LogicException('Audit logs are immutable and cannot be deleted.');
        });
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
