<?php

namespace App\Filament\Resources\TelegramChannelResource\Pages;

use App\Filament\Resources\TelegramChannelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTelegramChannel extends CreateRecord
{
    protected static string $resource = TelegramChannelResource::class;

    protected static ?string $title = 'افزودن کانال تلگرام';
}
