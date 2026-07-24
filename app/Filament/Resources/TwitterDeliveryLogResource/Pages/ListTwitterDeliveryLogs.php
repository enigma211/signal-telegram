<?php

namespace App\Filament\Resources\TwitterDeliveryLogResource\Pages;

use App\Filament\Resources\TwitterDeliveryLogResource;
use Filament\Resources\Pages\ListRecords;

class ListTwitterDeliveryLogs extends ListRecords
{
    protected static string $resource = TwitterDeliveryLogResource::class;

    protected static ?string $title = 'لاگ ارسال توییتر';
}
