<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TelegramDeliveryLogResource\Pages;
use App\Models\TelegramDeliveryLog;
use App\Services\TelegramDeliveryLogger;
use App\Services\TelegramService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TelegramDeliveryLogResource extends Resource
{
    protected static ?string $model = TelegramDeliveryLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'لاگ ارسال ناموفق';

    protected static ?string $modelLabel = 'لاگ ارسال';

    protected static ?string $pluralModelLabel = 'لاگ‌های ارسال تلگرام';

    protected static ?string $navigationGroup = 'تلگرام';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $count = TelegramDeliveryLog::query()->where('status', 'failed')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('context')->label('نوع')->disabled(),
                Forms\Components\TextInput::make('chat_id')->label('Chat ID')->disabled(),
                Forms\Components\Textarea::make('message_text')->label('متن پیام')->rows(8)->disabled()->columnSpanFull(),
                Forms\Components\Textarea::make('error_message')->label('خطا')->rows(3)->disabled()->columnSpanFull(),
                Forms\Components\TextInput::make('status')->label('وضعیت')->disabled(),
                Forms\Components\TextInput::make('attempts')->label('تلاش‌ها')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('context')
                    ->label('نوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'signal' => 'سیگنال',
                        'signal_update' => 'آپدیت',
                        'signal_result' => 'نتیجه',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('recipient_type')
                    ->label('گیرنده')
                    ->formatStateUsing(fn (string $state): string => $state === 'channel' ? 'کانال' : 'کاربر'),
                Tables\Columns\TextColumn::make('chat_id')
                    ->label('Chat ID')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('message_text')
                    ->label('پیام')
                    ->limit(40)
                    ->wrap(),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('خطا')
                    ->limit(50)
                    ->wrap()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'failed' => 'ناموفق',
                        'sent' => 'ارسال شد',
                        'abandoned' => 'رها شده',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'failed' => 'danger',
                        'sent' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('attempts')->label('تلاش')->sortable(),
                Tables\Columns\TextColumn::make('last_attempted_at')
                    ->label('آخرین تلاش')
                    ->jalaliDateTime()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'failed' => 'ناموفق',
                        'sent' => 'ارسال شد',
                        'abandoned' => 'رها شده',
                    ]),
                Tables\Filters\SelectFilter::make('context')
                    ->label('نوع')
                    ->options([
                        'signal' => 'سیگنال',
                        'signal_update' => 'آپدیت',
                        'signal_result' => 'نتیجه',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('retry')
                    ->label('ارسال مجدد')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (TelegramDeliveryLog $record): bool => $record->status === 'failed')
                    ->action(function (TelegramDeliveryLog $record): void {
                        $ok = app(TelegramDeliveryLogger::class)->retry(
                            $record,
                            app(TelegramService::class)
                        );

                        if ($ok) {
                            Notification::make()->title('ارسال موفق بود')->success()->send();
                        } else {
                            Notification::make()->title('ارسال مجدد ناموفق بود')->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('abandon')
                    ->label('رها کردن')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (TelegramDeliveryLog $record): bool => $record->status === 'failed')
                    ->action(function (TelegramDeliveryLog $record): void {
                        $record->update(['status' => 'abandoned']);
                        Notification::make()->title('لاگ رها شد')->success()->send();
                    }),
                Tables\Actions\ViewAction::make()->label('مشاهده'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('retrySelected')
                    ->label('ارسال مجدد انتخاب‌شده‌ها')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function ($records): void {
                        $logger = app(TelegramDeliveryLogger::class);
                        $telegram = app(TelegramService::class);
                        $ok = 0;
                        $fail = 0;

                        foreach ($records as $record) {
                            if ($record->status !== 'failed') {
                                continue;
                            }
                            $logger->retry($record, $telegram) ? $ok++ : $fail++;
                        }

                        Notification::make()
                            ->title("موفق: {$ok} | ناموفق: {$fail}")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelegramDeliveryLogs::route('/'),
            'view' => Pages\ViewTelegramDeliveryLog::route('/{record}'),
        ];
    }
}
