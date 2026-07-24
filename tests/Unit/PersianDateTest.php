<?php

namespace Tests\Unit;

use App\Support\PersianDate;
use PHPUnit\Framework\TestCase;

class PersianDateTest extends TestCase
{
    public function test_converts_known_gregorian_date_to_jalali(): void
    {
        // 2026-07-24 ≈ 1405-05-02
        [$y, $m, $d] = PersianDate::gregorianToJalali(2026, 7, 24);

        $this->assertSame(1405, $y);
        $this->assertSame(5, $m);
        $this->assertSame(2, $d);
    }

    public function test_formats_with_persian_digits(): void
    {
        $formatted = PersianDate::format('2026-07-24 11:30:00', 'Y/m/d H:i');

        $this->assertSame('۱۴۰۵/۰۵/۰۲ ۱۱:۳۰', $formatted);
    }
}
