<?php

namespace App\Modules\Media\Services;

use App\Modules\Media\Contracts\VirusScanner;

/**
 * Default hook-ready virus scanner stub (explicitly disabled until ClamAV/daemon is configured on VPS).
 */
final class NullVirusScanner implements VirusScanner
{
    public function isEnabled(): bool
    {
        return false;
    }

    public function scanPath(string $absolutePath): void
    {
        // Hook point for production ClamAV integration.
    }
}
