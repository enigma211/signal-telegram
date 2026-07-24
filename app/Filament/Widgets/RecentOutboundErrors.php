<?php

namespace App\Filament\Widgets;

use App\Models\TelegramDeliveryLog;
use App\Models\TwitterDeliveryLog;
use Filament\Widgets\Widget;

class RecentOutboundErrors extends Widget
{
    protected static string $view = 'filament.widgets.recent-outbound-errors';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    /**
     * @return list<array{channel: string, context: string, error: string, when: string, sort: int, url: string}>
     */
    public function getErrors(): array
    {
        $telegram = TelegramDeliveryLog::query()
            ->where('status', 'failed')
            ->latest('id')
            ->limit(15)
            ->get()
            ->map(fn (TelegramDeliveryLog $log): array => [
                'channel' => 'تلگرام',
                'context' => match ($log->context) {
                    'signal' => 'سیگنال',
                    'signal_update' => 'آپدیت',
                    'signal_result' => 'نتیجه',
                    default => $log->context,
                },
                'error' => $log->error_message ?: '—',
                'when' => optional($log->created_at)->diffForHumans() ?? '—',
                'sort' => $log->created_at?->timestamp ?? 0,
                'url' => route('filament.admin.resources.telegram-delivery-logs.view', ['record' => $log]),
            ]);

        $twitter = TwitterDeliveryLog::query()
            ->where('status', 'failed')
            ->latest('id')
            ->limit(15)
            ->get()
            ->map(fn (TwitterDeliveryLog $log): array => [
                'channel' => 'توییتر',
                'context' => match ($log->context) {
                    'signal' => 'سیگنال',
                    'signal_result' => 'نتیجه',
                    default => $log->context,
                },
                'error' => $log->error_message ?: '—',
                'when' => optional($log->created_at)->diffForHumans() ?? '—',
                'sort' => $log->created_at?->timestamp ?? 0,
                'url' => route('filament.admin.resources.twitter-delivery-logs.view', ['record' => $log]),
            ]);

        return $telegram
            ->concat($twitter)
            ->sortByDesc('sort')
            ->take(20)
            ->values()
            ->all();
    }
}
