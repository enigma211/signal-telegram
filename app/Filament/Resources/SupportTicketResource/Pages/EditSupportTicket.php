<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupportTicket extends EditRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected static ?string $title = 'ویرایش تیکت پشتیبانی';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('مشاهده'),
        ];
    }
}
