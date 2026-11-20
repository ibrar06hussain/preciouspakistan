<?php

namespace App\Modules\Media\Contracts;

interface VirusScanner
{
    public function isEnabled(): bool;

    /**
     * Throws RuntimeException if malware is detected.
     */
    public function scanPath(string $absolutePath): void;
}
