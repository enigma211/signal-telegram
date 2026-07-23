<?php

namespace App\Filament\Widgets;

use App\Enums\MarketType;
use App\Enums\SubscriptionTier;
use App\Models\TelegramUser;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActiveVipStats extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $forex = TelegramUser::query()
            ->eligibleForMarket(MarketType::Forex)
            ->count();

        $crypto = TelegramUser::query()
            ->eligibleForMarket(MarketType::Crypto)
            ->count();

        $full = TelegramUser::query()
            ->activeVip()
            ->where('subscription_tier', SubscriptionTier::VipFull->value)
            ->count();

        return [
            Stat::make('VIP فعال فارکس', number_format($forex))
                ->description('دسترسی به سیگنال فارکس')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
            Stat::make('VIP فعال کریپتو', number_format($crypto))
                ->description('دسترسی به سیگنال کریپتو')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
            Stat::make('VIP کامل', number_format($full))
                ->description('اشتراک فول فعال')
                ->descriptionIcon('heroicon-m-star')
                ->color('primary'),
        ];
    }
}
