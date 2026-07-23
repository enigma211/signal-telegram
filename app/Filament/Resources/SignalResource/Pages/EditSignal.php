<?php

namespace App\Filament\Resources\SignalResource\Pages;

use App\Enums\SignalResult;
use App\Filament\Resources\SignalResource;
use App\Jobs\BroadcastResultJob;
use App\Jobs\BroadcastSignalJob;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSignal extends EditRecord
{
    protected static string $resource = SignalResource::class;

    protected static ?string $title = 'ویرایش سیگنال';

    protected ?SignalResult $resultBeforeSave = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('broadcastSignal')
                ->label('ارسال مجدد سیگنال')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (): void {
                    BroadcastSignalJob::dispatch($this->record);
                    Notification::make()->title('سیگنال به صف ارسال اضافه شد')->success()->send();
                }),
            Actions\Action::make('broadcastResult')
                ->label('ارسال نتیجه')
                ->icon('heroicon-o-flag')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->result !== SignalResult::Pending)
                ->action(function (): void {
                    BroadcastResultJob::dispatch($this->record->fresh());
                    Notification::make()->title('نتیجه به صف ارسال اضافه شد')->success()->send();
                }),
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }

    protected function beforeSave(): void
    {
        $this->resultBeforeSave = $this->record->result;
    }

    protected function afterSave(): void
    {
        $record = $this->record->fresh();

        if (
            $record
            && $record->result !== SignalResult::Pending
            && $this->resultBeforeSave !== $record->result
        ) {
            BroadcastResultJob::dispatch($record);

            Notification::make()
                ->title('نتیجه ذخیره و به صف ارسال اضافه شد')
                ->success()
                ->send();
        }
    }
}
