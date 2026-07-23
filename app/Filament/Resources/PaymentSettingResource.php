<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentSettingResource\Pages;
use App\Models\PaymentSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentSettingResource extends Resource
{
    protected static ?string $model = PaymentSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'تنظیمات پرداخت VIP';

    protected static ?string $modelLabel = 'تنظیمات پرداخت';

    protected static ?string $pluralModelLabel = 'تنظیمات پرداخت';

    protected static ?string $navigationGroup = 'کاربران و مالی';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('آدرس ولت دریافت')->schema([
                    Forms\Components\TextInput::make('wallet_trc20')
                        ->label('ولت TRC20')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('wallet_bep20')
                        ->label('ولت BEP20')
                        ->maxLength(255),
                    Forms\Components\Select::make('default_network')
                        ->label('شبکه پیش‌فرض')
                        ->options([
                            'TRC20' => 'TRC20',
                            'BEP20' => 'BEP20',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('currency')
                        ->label('ارز')
                        ->default('USDT')
                        ->required(),
                ])->columns(2),
                Forms\Components\Section::make('قیمت و مدت')->schema([
                    Forms\Components\TextInput::make('price_forex')
                        ->label('قیمت VIP فارکس')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('price_crypto')
                        ->label('قیمت VIP کریپتو')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('price_full')
                        ->label('قیمت VIP کامل')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('subscription_days')
                        ->label('مدت اشتراک (روز)')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('vip_reminder_days')
                        ->label('اعلان انقضا (چند روز قبل)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(30)
                        ->default(3)
                        ->required()
                        ->helperText('مثلاً ۳ یعنی ۳ روز قبل از انقضا به کاربر پیام می‌رود.'),
                    Forms\Components\TextInput::make('referral_percent')
                        ->label('درصد پاداش معرفی')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->required(),
                    Forms\Components\Toggle::make('auto_confirm_verified_tx')
                        ->label('تأیید خودکار پس از verify زنجیره')
                        ->helperText('اگر هش روی شبکه تأیید شود، VIP خودکار فعال می‌شود.')
                        ->default(true)
                        ->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wallet_trc20')->label('TRC20')->limit(18)->placeholder('—'),
                Tables\Columns\TextColumn::make('wallet_bep20')->label('BEP20')->limit(18)->placeholder('—'),
                Tables\Columns\TextColumn::make('price_forex')->label('فارکس'),
                Tables\Columns\TextColumn::make('price_crypto')->label('کریپتو'),
                Tables\Columns\TextColumn::make('price_full')->label('کامل'),
                Tables\Columns\TextColumn::make('subscription_days')->label('روز اشتراک'),
                Tables\Columns\TextColumn::make('referral_percent')->label('پاداش %'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش'),
            ])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return PaymentSetting::query()->count() === 0;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePaymentSettings::route('/'),
        ];
    }
}
