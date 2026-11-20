<?php

namespace App\Modules\Identity\Contracts;

use App\Models\User;

interface MfaProvider
{
    public function isConfigured(): bool;

    public function isEnabledFor(User $user): bool;

    /**
     * @return array{enabled: bool, driver: string, secret?: string, qr_uri?: string}
     */
    public function beginEnrollment(User $user): array;

    public function verifyCode(User $user, string $code): bool;
}
