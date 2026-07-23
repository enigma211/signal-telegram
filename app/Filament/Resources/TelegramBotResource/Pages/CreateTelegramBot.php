<?php

namespace App\Filament\Resources\TelegramBotResource\Pages;

use App\Filament\Resources\TelegramBotResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTelegramBot extends CreateRecord
{
    protected static string $resource = TelegramBotResource::class;

    protected static ?string $title = 'افزودن ربات تلگرام';
}
