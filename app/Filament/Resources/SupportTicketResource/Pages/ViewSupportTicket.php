<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Services\SupportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected static ?string $title = 'مشاهده تیکت پشتیبانی';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label('پاسخ به کاربر')
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn (): bool => $this->record->status !== 'closed')
                ->form([
                    Forms\Components\Textarea::make('body')
                        ->label('متن پاسخ')
                        ->required()
                        ->rows(5),
                ])
                ->action(function (array $data): void {
                    app(SupportService::class)->replyAsAdmin(
                        $this->record,
                        $data['body'],
                        auth()->user()
                    );

                    Notification::make()->title('پاسخ ارسال شد')->success()->send();
                    $this->refreshFormData(['status', 'last_message_at']);
                }),
            Actions\Action::make('close')
                ->label('بستن تیکت')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status !== 'closed')
                ->action(function (): void {
                    app(SupportService::class)->closeTicket($this->record);
                    Notification::make()->title('تیکت بسته شد')->success()->send();
                    $this->refreshFormData(['status']);
                }),
            Actions\EditAction::make()->label('ویرایش'),
        ];
    }
}
