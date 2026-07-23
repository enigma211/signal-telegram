<?php

namespace App\Filament\Resources\TelegramDeliveryLogResource\Pages;

use App\Filament\Resources\TelegramDeliveryLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTelegramDeliveryLog extends ViewRecord
{
    protected static string $resource = TelegramDeliveryLogResource::class;

    protected static ?string $title = 'جزئیات لاگ ارسال';
}
