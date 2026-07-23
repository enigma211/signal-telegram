<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MonthlyRevenueStats extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $confirmedSubscription = fn () => Transaction::query()
            ->where('status', TransactionStatus::Confirmed->value)
            ->where('type', TransactionType::Subscription->value)
            ->where('currency', 'USDT');

        $thisMonth = (float) $confirmedSubscription()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $lastMonth = (float) $confirmedSubscription()
            ->whereBetween('created_at', [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ])
            ->sum('amount');

        $today = (float) $confirmedSubscription()
            ->whereDate('created_at', today())
            ->sum('amount');

        $change = $lastMonth > 0
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1)
            : ($thisMonth > 0 ? 100 : 0);

        $changeLabel = ($change >= 0 ? '+' : '').$change.'% نسبت به ماه قبل';

        return [
            Stat::make('درآمد این ماه', number_format($thisMonth, 2).' USDT')
                ->description($changeLabel)
                ->descriptionIcon($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($change >= 0 ? 'success' : 'danger'),
            Stat::make('درآمد ماه قبل', number_format($lastMonth, 2).' USDT')
                ->description(now()->subMonthNoOverflow()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('gray'),
            Stat::make('درآمد امروز', number_format($today, 2).' USDT')
                ->description('اشتراک‌های تأییدشده امروز')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }
}
