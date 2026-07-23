<?php

namespace App\Filament\Resources\SignalUpdateResource\Pages;

use App\Filament\Resources\SignalUpdateResource;
use App\Jobs\BroadcastSignalUpdateJob;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSignalUpdate extends EditRecord
{
    protected static string $resource = SignalUpdateResource::class;

    protected static ?string $title = 'ویرایش آپدیت سیگنال';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('broadcast')
                ->label('ارسال مجدد')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (): void {
                    BroadcastSignalUpdateJob::dispatch($this->record);
                    Notification::make()->title('آپدیت به صف ارسال اضافه شد')->success()->send();
                }),
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
