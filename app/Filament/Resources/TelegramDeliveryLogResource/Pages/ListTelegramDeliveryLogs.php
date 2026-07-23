<?php

namespace App\Filament\Resources\TelegramDeliveryLogResource\Pages;

use App\Filament\Resources\TelegramDeliveryLogResource;
use Filament\Resources\Pages\ListRecords;

class ListTelegramDeliveryLogs extends ListRecords
{
    protected static string $resource = TelegramDeliveryLogResource::class;

    protected static ?string $title = 'لاگ ارسال ناموفق تلگرام';
}
