<?php

namespace App\Modules\Rbac\Policies;

use App\Models\User;
use App\Modules\Rbac\Models\AuditLog;

class AuditLogPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('audit.view');
    }

    public function view(User $actor, AuditLog $auditLog): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return false;
    }

    public function update(User $actor, AuditLog $auditLog): bool
    {
        return false;
    }

    public function delete(User $actor, AuditLog $auditLog): bool
    {
        return false;
    }
}
