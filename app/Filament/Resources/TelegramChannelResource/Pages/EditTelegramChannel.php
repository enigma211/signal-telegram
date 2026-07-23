<?php

namespace App\Filament\Resources\TelegramChannelResource\Pages;

use App\Filament\Resources\TelegramChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTelegramChannel extends EditRecord
{
    protected static string $resource = TelegramChannelResource::class;

    protected static ?string $title = 'ویرایش کانال تلگرام';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
