<?php

namespace App\Modules\Rbac\Services;

use App\Models\User;
use App\Modules\Rbac\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $beforeState
     * @param  array<string, mixed>|null  $afterState
     */
    public function record(
        ?User $actor,
        string $event,
        string $summary,
        ?Model $auditable = null,
        ?User $targetUser = null,
        ?array $beforeState = null,
        ?array $afterState = null,
    ): AuditLog {
        $request = app()->bound('request') ? request() : null;

        return AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'event' => $event,
            'auditable_type' => $auditable !== null ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'target_user_id' => $targetUser?->id,
            'summary' => mb_substr($summary, 0, 255),
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? mb_substr((string) $request->userAgent(), 0, 255) : null,
            'created_at' => now(),
        ]);
    }
}
