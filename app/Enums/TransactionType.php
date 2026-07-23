<?php

namespace App\Enums;

enum TransactionType: string
{
    case Subscription = 'subscription';
    case ReferralReward = 'referral_reward';

    public function label(): string
    {
        return match ($this) {
            self::Subscription => 'اشتراک',
            self::ReferralReward => 'پاداش معرفی',
        };
    }
}
