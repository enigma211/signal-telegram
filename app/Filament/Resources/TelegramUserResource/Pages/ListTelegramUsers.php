<?php

namespace App\Filament\Resources\TelegramUserResource\Pages;

use App\Enums\BotLanguage;
use App\Enums\SubscriptionTier;
use App\Filament\Resources\TelegramUserResource;
use App\Models\TelegramUser;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTelegramUsers extends ListRecords
{
    protected static string $resource = TelegramUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('blockByTelegramId')
                ->label('مسدود کردن با آیدی')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->form([
                    Forms\Components\TextInput::make('telegram_id')
                        ->label('آیدی عددی تلگرام')
                        ->required()
                        ->helperText('شناسه عددی کاربر (مثلاً 93010245). اگر کاربر هنوز عضو نشده باشد هم ثبت و مسدود می‌شود.'),
                    Forms\Components\Select::make('bot_language')
                        ->label('زبان پیش‌فرض رکورد')
                        ->options(collect(BotLanguage::cases())->mapWithKeys(
                            fn (BotLanguage $case) => [$case->value => $case->label()]
                        ))
                        ->default(BotLanguage::Fa->value)
                        ->required()
                        ->helperText('فقط اگر کاربر جدید ساخته شود استفاده می‌شود. مسدودی برای هر دو ربات اعمال می‌شود.'),
                    Forms\Components\Textarea::make('blocked_reason')
                        ->label('دلیل (اختیاری)')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $telegramId = preg_replace('/\D+/', '', (string) $data['telegram_id']);

                    if ($telegramId === '') {
                        Notification::make()
                            ->title('آیدی تلگرام معتبر نیست')
                            ->danger()
                            ->send();

                        return;
                    }

                    $user = TelegramUser::query()->firstOrCreate(
                        ['telegram_id' => $telegramId],
                        [
                            'bot_language' => BotLanguage::from($data['bot_language']),
                            'subscription_tier' => SubscriptionTier::Free,
                        ]
                    );

                    $user->block($data['blocked_reason'] ?? null);

                    Notification::make()
                        ->title('کاربر مسدود شد')
                        ->body('آیدی: '.$telegramId)
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make()->label('ایجاد کاربر'),
        ];
    }
}
