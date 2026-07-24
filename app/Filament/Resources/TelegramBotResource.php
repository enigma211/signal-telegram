<?php

namespace App\Filament\Resources;

use App\Enums\BotLanguage;
use App\Filament\Resources\TelegramBotResource\Pages;
use App\Models\TelegramBot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;

class TelegramBotResource extends Resource
{
    protected static ?string $model = TelegramBot::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'ربات‌های تلگرام';

    protected static ?string $modelLabel = 'ربات تلگرام';

    protected static ?string $pluralModelLabel = 'ربات‌های تلگرام';

    protected static ?string $navigationGroup = 'تلگرام';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تنظیمات ربات')->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('نام نمایشی')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('ربات فارسی'),
                    Forms\Components\Select::make('language')
                        ->label('زبان ربات')
                        ->options(collect(BotLanguage::cases())->mapWithKeys(
                            fn (BotLanguage $case) => [$case->value => $case->label()]
                        ))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('برای هر زبان فقط یک ربات فعال تعریف کنید.'),
                    Forms\Components\TextInput::make('bot_token')
                        ->label('توکن ربات')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->maxLength(255)
                        ->helperText('توکن را از @BotFather کپی کنید. در ویرایش فقط در صورت تغییر وارد کنید.'),
                    Forms\Components\TextInput::make('bot_username')
                        ->label('یوزرنیم ربات')
                        ->prefix('@')
                        ->maxLength(255)
                        ->helperText('بدون @ وارد کنید؛ برای لینک لندینگ استفاده می‌شود.'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('فعال')
                        ->default(true)
                        ->required(),
                    Forms\Components\Placeholder::make('webhook_info')
                        ->label('وضعیت Webhook')
                        ->content(fn (?TelegramBot $record): string => $record?->webhook_set_at
                            ? 'آخرین تنظیم: '.jalali($record->webhook_set_at).' (نیاز به TELEGRAM_WEBHOOK_SECRET)'
                            : 'هنوز تنظیم نشده — ابتدا TELEGRAM_WEBHOOK_SECRET را در .env بگذارید.'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('language')
                    ->label('زبان')
                    ->badge()
                    ->formatStateUsing(fn (BotLanguage $state): string => $state->label())
                    ->color(fn (BotLanguage $state): string => match ($state) {
                        BotLanguage::Fa => 'success',
                        BotLanguage::En => 'info',
                    }),
                Tables\Columns\TextColumn::make('bot_username')
                    ->label('یوزرنیم')
                    ->formatStateUsing(fn (?string $state): string => $state ? '@'.$state : '—')
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
                Tables\Columns\TextColumn::make('channels_count')
                    ->counts('channels')
                    ->label('کانال‌ها'),
                Tables\Columns\TextColumn::make('webhook_set_at')
                    ->label('Webhook')
                    ->jalaliDateTime()
                    ->placeholder('تنظیم نشده'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('بروزرسانی')
                    ->jalaliDateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('setWebhook')
                    ->label('تنظیم Webhook')
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('تنظیم Webhook تلگرام')
                    ->modalDescription('آیا Webhook این ربات روی آدرس فعلی سایت تنظیم شود؟')
                    ->action(function (TelegramBot $record): void {
                        $secret = config('services.telegram.webhook_secret');

                        if (blank($secret)) {
                            Notification::make()
                                ->title('TELEGRAM_WEBHOOK_SECRET تنظیم نشده')
                                ->body('ابتدا در فایل .env یک secret تصادفی بگذارید، سپس دوباره تلاش کنید.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $url = rtrim(config('app.url'), '/')
                            .'/api/telegram/webhook/'
                            .$record->language->value;

                        $response = Http::asForm()->post(
                            "https://api.telegram.org/bot{$record->bot_token}/setWebhook",
                            [
                                'url' => $url,
                                'secret_token' => $secret,
                                'drop_pending_updates' => false,
                            ]
                        );

                        if ($response->successful() && data_get($response->json(), 'ok') === true) {
                            $record->update(['webhook_set_at' => now()]);

                            Notification::make()
                                ->title('Webhook با موفقیت تنظیم شد')
                                ->body($url.' (با secret_token)')
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('خطا در تنظیم Webhook')
                            ->body(data_get($response->json(), 'description', 'درخواست ناموفق بود.'))
                            ->danger()
                            ->send();
                    }),
                Tables\Actions\EditAction::make()->label('ویرایش'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف'),
                ]),
            ])
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelegramBots::route('/'),
            'create' => Pages\CreateTelegramBot::route('/create'),
            'edit' => Pages\EditTelegramBot::route('/{record}/edit'),
        ];
    }
}
