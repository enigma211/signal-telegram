<?php

namespace App\Filament\Resources\PaymentSettingResource\Pages;

use App\Filament\Resources\PaymentSettingResource;
use App\Models\PaymentSetting;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePaymentSettings extends ManageRecords
{
    protected static string $resource = PaymentSettingResource::class;

    protected static ?string $title = 'تنظیمات پرداخت VIP';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('ایجاد تنظیمات')
                ->visible(fn (): bool => PaymentSetting::query()->count() === 0),
        ];
    }

    public function mount(): void
    {
        parent::mount();
        PaymentSetting::current();
    }
}
