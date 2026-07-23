<?php

namespace App\Enums;

enum SignalStatus: string
{
    case Pending = 'pending';
    case Broadcasted = 'broadcasted';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار ارسال',
            self::Broadcasted => 'ارسال شده',
        };
    }
}
