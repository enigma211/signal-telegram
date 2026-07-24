<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TwitterSettingResource\Pages;
use App\Models\TwitterSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TwitterSettingResource extends Resource
{
    protected static ?string $model = TwitterSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-hashtag';

    protected static ?string $navigationLabel = 'تنظیمات توییتر (X)';

    protected static ?string $modelLabel = 'تنظیمات توییتر';

    protected static ?string $pluralModelLabel = 'تنظیمات توییتر';

    protected static ?string $navigationGroup = 'پیام‌رسانی';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('وضعیت انتشار')
                    ->description('سیگنال‌های انگلیسی ربات، هم‌زمان با تلگرام در اکانت X پست می‌شوند.')
                    ->schema([
                        Forms\Components\Toggle::make('enabled')
                            ->label('فعال‌سازی ارسال به توییتر')
                            ->helperText('اگر خاموش باشد هیچ توییتی ارسال نمی‌شود.')
                            ->default(false)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('post_signals')
                            ->label('ارسال سیگنال جدید')
                            ->default(true),
                        Forms\Components\Toggle::make('post_results')
                            ->label('ارسال نتیجه سیگنال')
                            ->default(true),
                        Forms\Components\Toggle::make('post_vip')
                            ->label('ارسال سیگنال‌های فقط VIP')
                            ->helperText('پیشنهاد: خاموش بماند تا سیگنال پولی عمومی نشود.')
                            ->default(false),
                        Forms\Components\Textarea::make('cta')
                            ->label('متن/لینک تبلیغ انتهای توییت (CTA)')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('مثلاً Start free: https://t.me/YourEnBot — اگر خالی باشد از یوزرنیم ربات EN ساخته می‌شود.')
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('کلیدهای API (X Developer)')
                    ->description('از developer.x.com با دسترسی Read and Write. بعد از تغییر دسترسی، Access Token را دوباره بسازید.')
                    ->schema([
                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key (Consumer Key)')
                            ->maxLength(255)
                            ->autocomplete(false),
                        Forms\Components\TextInput::make('api_secret')
                            ->label('API Secret (Consumer Secret)')
                            ->password()
                            ->revealable()
                            ->autocomplete(false)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('برای حفظ مقدار فعلی، هنگام ویرایش خالی بگذارید.'),
                        Forms\Components\TextInput::make('access_token')
                            ->label('Access Token')
                            ->maxLength(255)
                            ->autocomplete(false),
                        Forms\Components\TextInput::make('access_token_secret')
                            ->label('Access Token Secret')
                            ->password()
                            ->revealable()
                            ->autocomplete(false)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('برای حفظ مقدار فعلی، هنگام ویرایش خالی بگذارید.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('enabled')
                    ->label('فعال')
                    ->boolean(),
                Tables\Columns\IconColumn::make('post_signals')
                    ->label('سیگنال')
                    ->boolean(),
                Tables\Columns\IconColumn::make('post_results')
                    ->label('نتیجه')
                    ->boolean(),
                Tables\Columns\IconColumn::make('post_vip')
                    ->label('VIP')
                    ->boolean(),
                Tables\Columns\TextColumn::make('cta')
                    ->label('CTA')
                    ->limit(40)
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('configured')
                    ->label('API')
                    ->getStateUsing(fn (TwitterSetting $record): bool => $record->isConfigured())
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش'),
            ])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return TwitterSetting::query()->count() === 0;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTwitterSettings::route('/'),
        ];
    }
}
