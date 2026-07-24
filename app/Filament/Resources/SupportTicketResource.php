<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Filament\Resources\SupportTicketResource\RelationManagers\MessagesRelationManager;
use App\Models\SupportTicket;
use App\Models\TelegramUser;
use App\Services\SupportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationLabel = 'پشتیبانی';

    protected static ?string $modelLabel = 'تیکت پشتیبانی';

    protected static ?string $pluralModelLabel = 'تیکت‌های پشتیبانی';

    protected static ?string $navigationGroup = 'کاربران و مالی';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $count = SupportTicket::query()->where('status', 'open')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات تیکت')->schema([
                    Forms\Components\Select::make('telegram_user_id')
                        ->label('کاربر')
                        ->relationship('telegramUser', 'telegram_id')
                        ->getOptionLabelFromRecordUsing(fn (TelegramUser $record): string => $record->displayName().' · '.$record->telegram_id)
                        ->searchable()
                        ->disabled(),
                    Forms\Components\TextInput::make('subject')
                        ->label('موضوع')
                        ->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->label('وضعیت')
                        ->options([
                            'open' => 'باز (منتظر پاسخ)',
                            'answered' => 'پاسخ داده شده',
                            'closed' => 'بسته',
                        ])
                        ->required(),
                    Forms\Components\DateTimePicker::make('last_message_at')
                        ->label('آخرین پیام')
                        ->jalali()
                        ->disabled(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('telegramUser.telegram_id')
                    ->label('کاربر')
                    ->formatStateUsing(fn ($state, $record): string => $record->telegramUser
                        ? $record->telegramUser->displayName()
                        : (string) $state)
                    ->description(fn ($record): ?string => $record->telegramUser
                        ? 'ID: '.$record->telegramUser->telegram_id
                        : null)
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('telegramUser', function ($q) use ($search): void {
                            $q->where('telegram_id', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->copyable(),
                Tables\Columns\TextColumn::make('telegramUser.bot_language')
                    ->label('زبان')
                    ->badge(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('موضوع')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'باز',
                        'answered' => 'پاسخ‌داده‌شده',
                        'closed' => 'بسته',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'warning',
                        'answered' => 'info',
                        'closed' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('messages_count')
                    ->counts('messages')
                    ->label('پیام‌ها'),
                Tables\Columns\TextColumn::make('last_message_at')
                    ->label('آخرین پیام')
                    ->jalaliDateTime()
                    ->sortable(),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'open' => 'باز',
                        'answered' => 'پاسخ‌داده‌شده',
                        'closed' => 'بسته',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('reply')
                    ->label('پاسخ')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn (SupportTicket $record): bool => $record->status !== 'closed')
                    ->form([
                        Forms\Components\Textarea::make('body')
                            ->label('متن پاسخ')
                            ->required()
                            ->rows(5),
                    ])
                    ->action(function (SupportTicket $record, array $data): void {
                        app(SupportService::class)->replyAsAdmin(
                            $record,
                            $data['body'],
                            auth()->user()
                        );

                        Notification::make()
                            ->title('پاسخ ارسال شد')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('close')
                    ->label('بستن')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (SupportTicket $record): bool => $record->status !== 'closed')
                    ->action(function (SupportTicket $record): void {
                        app(SupportService::class)->closeTicket($record);
                        Notification::make()->title('تیکت بسته شد')->success()->send();
                    }),
                Tables\Actions\ViewAction::make()->label('مشاهده'),
                Tables\Actions\EditAction::make()->label('ویرایش'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
