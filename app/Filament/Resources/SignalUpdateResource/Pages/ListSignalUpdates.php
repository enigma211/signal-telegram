<?php

namespace App\Filament\Resources\SignalUpdateResource\Pages;

use App\Filament\Resources\SignalUpdateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSignalUpdates extends ListRecords
{
    protected static string $resource = SignalUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('ایجاد آپدیت'),
        ];
    }
}
