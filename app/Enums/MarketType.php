<?php

namespace App\Enums;

enum MarketType: string
{
    case Forex = 'forex';
    case Crypto = 'crypto';

    public function label(): string
    {
        return match ($this) {
            self::Forex => 'فارکس',
            self::Crypto => 'کریپتو',
        };
    }
}
