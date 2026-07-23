<?php

namespace App\Filament\Resources\TelegramBotResource\Pages;

use App\Filament\Resources\TelegramBotResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTelegramBots extends ListRecords
{
    protected static string $resource = TelegramBotResource::class;

    protected static ?string $title = 'ربات‌های تلگرام';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('افزودن ربات'),
        ];
    }
}
