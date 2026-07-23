<?php

namespace App\Enums;

enum TargetAudience: string
{
    case All = 'all';
    case VipOnly = 'vip_only';

    public function label(): string
    {
        return match ($this) {
            self::All => 'همه کاربران (رایگان + VIP)',
            self::VipOnly => 'فقط VIP',
        };
    }
}
