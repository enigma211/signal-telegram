<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

class PersianDate
{
    /**
     * Format a Gregorian date/time as Jalali (شمسی).
     */
    public static function format(
        DateTimeInterface|string|null $value,
        string $format = 'Y/m/d H:i',
        bool $persianDigits = true
    ): string {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            $date = $value instanceof Carbon
                ? $value->copy()
                : Carbon::parse($value);
        } catch (\Throwable) {
            return '—';
        }

        [$jy, $jm, $jd] = self::gregorianToJalali(
            (int) $date->format('Y'),
            (int) $date->format('n'),
            (int) $date->format('j')
        );

        $replacements = [
            'Y' => sprintf('%04d', $jy),
            'y' => sprintf('%02d', $jy % 100),
            'm' => sprintf('%02d', $jm),
            'n' => (string) $jm,
            'd' => sprintf('%02d', $jd),
            'j' => (string) $jd,
            'H' => $date->format('H'),
            'i' => $date->format('i'),
            's' => $date->format('s'),
        ];

        $result = '';
        $length = strlen($format);

        for ($i = 0; $i < $length; $i++) {
            $char = $format[$i];
            $result .= $replacements[$char] ?? $char;
        }

        return $persianDigits ? self::toPersianDigits($result) : $result;
    }

    public static function date(DateTimeInterface|string|null $value): string
    {
        return self::format($value, 'Y/m/d');
    }

    public static function dateTime(DateTimeInterface|string|null $value): string
    {
        return self::format($value, 'Y/m/d H:i');
    }

    /**
     * @return array{0: int, 1: int, 2: int} [year, month, day]
     */
    public static function gregorianToJalali(int $gy, int $gm, int $gd): array
    {
        $gDaysInMonth = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666
            + (365 * $gy)
            + intdiv($gy2 + 3, 4)
            - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400)
            + $gd
            + $gDaysInMonth[$gm - 1];

        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    public static function toPersianDigits(string $value): string
    {
        return strtr($value, [
            '0' => '۰',
            '1' => '۱',
            '2' => '۲',
            '3' => '۳',
            '4' => '۴',
            '5' => '۵',
            '6' => '۶',
            '7' => '۷',
            '8' => '۸',
            '9' => '۹',
        ]);
    }
}
