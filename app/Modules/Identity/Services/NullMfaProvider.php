<?php

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Contracts\MfaProvider;

final class NullMfaProvider implements MfaProvider
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function isEnabledFor(User $user): bool
    {
        return false;
    }

    public function beginEnrollment(User $user): array
    {
        return [
            'enabled' => false,
            'driver' => 'null',
        ];
    }

    public function verifyCode(User $user, string $code): bool
    {
        return false;
    }
}
