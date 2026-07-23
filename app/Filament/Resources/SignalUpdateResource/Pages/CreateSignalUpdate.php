<?php

namespace App\Filament\Resources\SignalUpdateResource\Pages;

use App\Filament\Resources\SignalUpdateResource;
use App\Jobs\BroadcastSignalUpdateJob;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSignalUpdate extends CreateRecord
{
    protected static string $resource = SignalUpdateResource::class;

    protected static ?string $title = 'ایجاد آپدیت سیگنال';

    protected function afterCreate(): void
    {
        BroadcastSignalUpdateJob::dispatch($this->record);

        Notification::make()
            ->title('آپدیت ذخیره و به صف ارسال اضافه شد')
            ->success()
            ->send();
    }
}
