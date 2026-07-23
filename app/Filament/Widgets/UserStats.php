<?php

namespace App\Filament\Widgets;

use App\Enums\BotLanguage;
use App\Models\TelegramUser;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalFa = TelegramUser::query()->language(BotLanguage::Fa)->count();
        $totalEn = TelegramUser::query()->language(BotLanguage::En)->count();

        return [
            Stat::make('کاربران فارسی', number_format($totalFa))
                ->description('ربات فارسی (FA)')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make('کاربران انگلیسی', number_format($totalEn))
                ->description('ربات انگلیسی (EN)')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
            Stat::make('کل کاربران', number_format($totalFa + $totalEn))
                ->description('مجموع هر دو ربات')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
