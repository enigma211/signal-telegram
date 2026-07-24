<?php

namespace App\Filament\Resources\TelegramUserResource\Pages;

use App\Filament\Resources\TelegramUserResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTelegramUser extends EditRecord
{
    protected static string $resource = TelegramUserResource::class;

    protected static ?string $title = 'ویرایش کاربر تلگرام';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('blockUser')
                ->label('مسدود کردن کاربر')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn (): bool => ! $this->record->is_blocked)
                ->requiresConfirmation()
                ->modalHeading('مسدود کردن کاربر')
                ->modalDescription('بعد از مسدودی، کاربر نمی‌تواند از ربات فارسی یا انگلیسی استفاده کند و سیگنال دریافت نمی‌کند.')
                ->form([
                    Forms\Components\Textarea::make('blocked_reason')
                        ->label('دلیل (اختیاری)')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record->block($data['blocked_reason'] ?? null);
                    $this->refreshFormData(['is_blocked', 'blocked_at', 'blocked_reason']);

                    Notification::make()
                        ->title('کاربر مسدود شد')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('unblockUser')
                ->label('رفع مسدودی')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->visible(fn (): bool => $this->record->is_blocked)
                ->requiresConfirmation()
                ->modalHeading('رفع مسدودی کاربر')
                ->action(function (): void {
                    $this->record->unblock();
                    $this->refreshFormData(['is_blocked', 'blocked_at', 'blocked_reason']);

                    Notification::make()
                        ->title('مسدودی برداشته شد')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
