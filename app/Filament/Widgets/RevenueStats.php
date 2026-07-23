<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStats extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $revenue = (float) Transaction::query()
            ->where('status', TransactionStatus::Confirmed->value)
            ->where('type', TransactionType::Subscription->value)
            ->where('currency', 'USDT')
            ->sum('amount');

        $pending = (float) Transaction::query()
            ->where('status', TransactionStatus::Pending->value)
            ->where('type', TransactionType::Subscription->value)
            ->where('currency', 'USDT')
            ->sum('amount');

        $referralRewards = (float) Transaction::query()
            ->whereIn('status', [
                TransactionStatus::Confirmed->value,
                TransactionStatus::Paid->value,
            ])
            ->where('type', TransactionType::ReferralReward->value)
            ->where('currency', 'USDT')
            ->sum('amount');

        $unpaidReferrals = (float) Transaction::query()
            ->where('status', TransactionStatus::Confirmed->value)
            ->where('type', TransactionType::ReferralReward->value)
            ->where('currency', 'USDT')
            ->sum('amount');

        return [
            Stat::make('درآمد کل USDT', number_format($revenue, 2).' USDT')
                ->description('تراکنش‌های اشتراک تأییدشده')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('در انتظار تأیید', number_format($pending, 2).' USDT')
                ->description('پرداخت‌های معلق')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('پاداش معرفی', number_format($referralRewards, 2).' USDT')
                ->description('طلب پرداخت‌نشده: '.number_format($unpaidReferrals, 2).' USDT')
                ->descriptionIcon('heroicon-m-gift')
                ->color('info'),
        ];
    }
}
