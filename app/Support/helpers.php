<?php

use App\Support\PersianDate;

if (! function_exists('jalali')) {
    function jalali(
        \DateTimeInterface|string|null $value,
        string $format = 'Y/m/d H:i',
        bool $persianDigits = true
    ): string {
        return PersianDate::format($value, $format, $persianDigits);
    }
}

if (! function_exists('jalali_date')) {
    function jalali_date(\DateTimeInterface|string|null $value): string
    {
        return PersianDate::date($value);
    }
}

if (! function_exists('jalali_datetime')) {
    function jalali_datetime(\DateTimeInterface|string|null $value): string
    {
        return PersianDate::dateTime($value);
    }
}
