<?php

namespace Tests\Unit\Phase1;

use App\Shared\Support\DisplayTimezone;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class DisplayTimezoneTest extends TestCase
{
    public function test_utc_timestamp_is_formatted_in_asia_karachi_timezone(): void
    {
        $utc = CarbonImmutable::create(2026, 6, 15, 10, 30, 0, 'UTC');

        $formatted = DisplayTimezone::format($utc, 'Y-m-d H:i T');

        $this->assertSame('Asia/Karachi', DisplayTimezone::timezone());
        $this->assertSame('2026-06-15 15:30 PKT', $formatted);
    }

    public function test_null_timestamp_returns_placeholder(): void
    {
        $this->assertSame('—', DisplayTimezone::format(null));
    }
}
