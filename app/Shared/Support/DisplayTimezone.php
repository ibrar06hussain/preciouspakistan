<?php

namespace App\Shared\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;

final class DisplayTimezone
{
    public static function timezone(): string
    {
        return (string) config('precious.display_timezone', config('app.display_timezone', 'Asia/Karachi'));
    }

    public static function toDisplay(?DateTimeInterface $dateTime): ?CarbonImmutable
    {
        if ($dateTime === null) {
            return null;
        }

        if ($dateTime instanceof CarbonInterface) {
            return $dateTime->toImmutable()->setTimezone(self::timezone());
        }

        return CarbonImmutable::instance($dateTime)->setTimezone(self::timezone());
    }

    public static function format(?DateTimeInterface $dateTime, string $format = 'd M Y, h:i A T'): string
    {
        $display = self::toDisplay($dateTime);

        return $display?->format($format) ?? '—';
    }

    public static function nowDisplay(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC')->setTimezone(self::timezone());
    }
}
