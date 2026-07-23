<?php

namespace App\Enums;

enum SubscriptionTier: string
{
    case Free = 'free';
    case VipForex = 'vip_forex';
    case VipCrypto = 'vip_crypto';
    case VipFull = 'vip_full';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'رایگان',
            self::VipForex => 'VIP فارکس',
            self::VipCrypto => 'VIP کریپتو',
            self::VipFull => 'VIP کامل',
        };
    }

    public function hasForexAccess(): bool
    {
        return in_array($this, [self::VipForex, self::VipFull], true);
    }

    public function hasCryptoAccess(): bool
    {
        return in_array($this, [self::VipCrypto, self::VipFull], true);
    }

    public function isVip(): bool
    {
        return $this !== self::Free;
    }
}
