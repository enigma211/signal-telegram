<?php

namespace App\Enums;

enum SignalResult: string
{
    case Pending = 'pending';
    case Win = 'win';
    case Loss = 'loss';
    case Neutral = 'neutral';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار',
            self::Win => 'برد',
            self::Loss => 'باخت',
            self::Neutral => 'خنثی',
        };
    }
}
