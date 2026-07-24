<?php

namespace App\Filament\Widgets;

use App\Models\TelegramDeliveryLog;
use App\Models\TwitterDeliveryLog;
use App\Services\SystemHealthChecker;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemHealthStats extends BaseWidget
{
    protected static ?int $sort = 0;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $report = app(SystemHealthChecker::class)->check();
        $checks = $report['checks'];

        $horizon = $checks['horizon']['status'] === 'ok';
        $redis = $checks['redis']['status'] === 'ok';
        $db = $checks['database']['status'] === 'ok';

        $telegramFailed = TelegramDeliveryLog::query()
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $twitterFailed = TwitterDeliveryLog::query()
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return [
            Stat::make('Horizon', $horizon ? 'فعال' : 'قطع')
                ->description($checks['horizon']['message'] ?? '')
                ->descriptionIcon($horizon ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->color($horizon ? 'success' : 'danger'),
            Stat::make('Redis', $redis ? 'فعال' : 'قطع')
                ->description($checks['redis']['message'] ?? '')
                ->descriptionIcon($redis ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->color($redis ? 'success' : 'danger'),
            Stat::make('دیتابیس', $db ? 'فعال' : 'قطع')
                ->description($checks['database']['message'] ?? '')
                ->descriptionIcon($db ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->color($db ? 'success' : 'danger'),
            Stat::make('خطا ۲۴س (تلگرام/توییتر)', number_format($telegramFailed).' / '.number_format($twitterFailed))
                ->description('ناموفق تلگرام / توییتر در ۲۴ ساعت اخیر')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color(($telegramFailed + $twitterFailed) > 0 ? 'warning' : 'success'),
        ];
    }
}
