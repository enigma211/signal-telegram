<?php

namespace App\Filament\Resources\SignalResource\Pages;

use App\Enums\SignalResult;
use App\Enums\SignalStatus;
use App\Filament\Resources\SignalResource;
use App\Jobs\BroadcastSignalJob;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSignal extends CreateRecord
{
    protected static string $resource = SignalResource::class;

    protected static ?string $title = 'ایجاد سیگنال';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = SignalStatus::Pending->value;
        $data['result'] ??= SignalResult::Pending->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        BroadcastSignalJob::dispatch($this->record);

        Notification::make()
            ->title('سیگنال ذخیره و به صف ارسال اضافه شد')
            ->success()
            ->send();
    }
}
