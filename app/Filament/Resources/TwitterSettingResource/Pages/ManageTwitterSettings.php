<?php

namespace App\Filament\Resources\TwitterSettingResource\Pages;

use App\Filament\Resources\TwitterSettingResource;
use App\Models\TwitterSetting;
use App\Services\TwitterService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Throwable;

class ManageTwitterSettings extends ManageRecords
{
    protected static string $resource = TwitterSettingResource::class;

    protected static ?string $title = 'تنظیمات توییتر (X)';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('testTweet')
                ->label('ارسال توییت تست')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('ارسال توییت تست؟')
                ->modalDescription('یک پیام کوتاه تست به اکانت متصل‌شده در X ارسال می‌شود.')
                ->action(function (TwitterService $twitter): void {
                    $settings = TwitterSetting::current();

                    if (! $settings->isConfigured()) {
                        Notification::make()
                            ->title('کلیدهای API کامل نیست')
                            ->danger()
                            ->send();

                        return;
                    }

                    if (! $settings->enabled) {
                        Notification::make()
                            ->title('ارسال توییتر خاموش است')
                            ->body('ابتدا «فعال‌سازی ارسال به توییتر» را روشن کنید.')
                            ->warning()
                            ->send();

                        return;
                    }

                    try {
                        $result = $twitter->post(
                            'Nova Signal test ✅ '.now()->timezone(config('app.timezone'))->format('Y-m-d H:i')
                        );

                        Notification::make()
                            ->title('توییت تست ارسال شد')
                            ->body('Tweet ID: '.($result['id'] ?: '—'))
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('خطا در ارسال به توییتر')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\CreateAction::make()
                ->label('ایجاد تنظیمات')
                ->visible(fn (): bool => TwitterSetting::query()->count() === 0),
        ];
    }

    public function mount(): void
    {
        parent::mount();
        TwitterSetting::current();
    }
}
