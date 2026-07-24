<?php

namespace App\Filament\Resources\TelegramUserResource\Pages;

use App\Filament\Resources\TelegramUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTelegramUser extends EditRecord
{
    protected static string $resource = TelegramUserResource::class;

    protected static ?string $title = 'ویرایش کاربر تلگرام';

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['is_blocked'])) {
            $data['blocked_at'] = $this->record->blocked_at ?? now();
        } else {
            $data['is_blocked'] = false;
            $data['blocked_at'] = null;
            $data['blocked_reason'] = null;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
