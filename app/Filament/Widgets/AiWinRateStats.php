<?php

namespace App\Filament\Widgets;

use App\Enums\SignalResult;
use App\Models\Signal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiWinRateStats extends BaseWidget
{
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $wins = Signal::query()->where('result', SignalResult::Win->value)->count();
        $losses = Signal::query()->where('result', SignalResult::Loss->value)->count();
        $neutral = Signal::query()->where('result', SignalResult::Neutral->value)->count();
        $decided = $wins + $losses;
        $winRate = $decided > 0 ? round(($wins / $decided) * 100, 1) : 0;

        return [
            Stat::make('نرخ برد هوش مصنوعی', $winRate.'%')
                ->description("برد: {$wins} | باخت: {$losses}")
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color($winRate >= 60 ? 'success' : ($winRate >= 45 ? 'warning' : 'danger')),
            Stat::make('سیگنال‌های برد', number_format($wins))
                ->description('نتیجه نهایی Win')
                ->color('success'),
            Stat::make('خنثی / بدون نتیجه', number_format($neutral))
                ->description('Neutral یا در انتظار')
                ->color('gray'),
        ];
    }
}
