<?php

namespace App\Filament\Resources;

use App\Enums\BotLanguage;
use App\Enums\SubscriptionTier;
use App\Filament\Resources\TelegramUserResource\Pages;
use App\Models\TelegramUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TelegramUserResource extends Resource
{
    protected static ?string $model = TelegramUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'کاربران تلگرام';

    protected static ?string $modelLabel = 'کاربر تلگرام';

    protected static ?string $pluralModelLabel = 'کاربران تلگرام';

    protected static ?string $navigationGroup = 'کاربران و مالی';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات کاربر')->schema([
                    Forms\Components\TextInput::make('telegram_id')
                        ->label('آیدی تلگرام')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->helperText('شناسه عددی ثابت تلگرام (برای ارسال پیام استفاده می‌شود).'),
                    Forms\Components\TextInput::make('first_name')
                        ->label('نام')
                        ->maxLength(255)
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('last_name')
                        ->label('نام خانوادگی')
                        ->maxLength(255)
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('username')
                        ->label('یوزرنیم')
                        ->prefix('@')
                        ->maxLength(255)
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('از تلگرام گرفته می‌شود؛ ممکن است خالی باشد یا عوض شود.'),
                    Forms\Components\Select::make('bot_language')
                        ->label('زبان ربات')
                        ->options(collect(BotLanguage::cases())->mapWithKeys(
                            fn (BotLanguage $case) => [$case->value => $case->label()]
                        ))
                        ->required(),
                    Forms\Components\Select::make('subscription_tier')
                        ->label('سطح اشتراک')
                        ->options(collect(SubscriptionTier::cases())->mapWithKeys(
                            fn (SubscriptionTier $case) => [$case->value => $case->label()]
                        ))
                        ->required(),
                    Forms\Components\DateTimePicker::make('vip_expires_at')
                        ->label('انقضای VIP'),
                    Forms\Components\TextInput::make('referral_code')
                        ->label('کد معرف')
                        ->disabled()
                        ->dehydrated(),
                    Forms\Components\Placeholder::make('referral_invite_url')
                        ->label('لینک دعوت')
                        ->content(fn (?TelegramUser $record): string => $record?->referral_code
                            ? $record->referralInviteUrl()
                            : '—'),
                    Forms\Components\Select::make('referred_by')
                        ->label('معرف')
                        ->relationship('referrer', 'telegram_id')
                        ->getOptionLabelFromRecordUsing(fn (TelegramUser $record): string => $record->displayName().' · '.$record->telegram_id)
                        ->searchable(['telegram_id', 'username', 'first_name', 'last_name'])
                        ->preload()
                        ->nullable(),
                    Forms\Components\TextInput::make('crypto_wallet_address')
                        ->label('آدرس کیف پول')
                        ->maxLength(255),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('کاربر')
                    ->state(fn (TelegramUser $record): string => $record->displayName())
                    ->description(fn (TelegramUser $record): string => 'ID: '.$record->telegram_id)
                    ->searchable(query: function ($query, string $search): void {
                        $query->where(function ($q) use ($search): void {
                            $q->where('telegram_id', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $query->orderBy('first_name', $direction)->orderBy('username', $direction);
                    }),
                Tables\Columns\TextColumn::make('telegram_id')
                    ->label('آیدی تلگرام')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('username')
                    ->label('یوزرنیم')
                    ->formatStateUsing(fn (?string $state): string => $state ? '@'.$state : '—')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('bot_language')
                    ->label('زبان')
                    ->badge()
                    ->formatStateUsing(fn (BotLanguage $state): string => $state->label())
                    ->color(fn (BotLanguage $state): string => match ($state) {
                        BotLanguage::Fa => 'success',
                        BotLanguage::En => 'info',
                    }),
                Tables\Columns\TextColumn::make('subscription_tier')
                    ->label('اشتراک')
                    ->badge()
                    ->formatStateUsing(fn (SubscriptionTier $state): string => $state->label())
                    ->color(fn (SubscriptionTier $state): string => match ($state) {
                        SubscriptionTier::Free => 'gray',
                        SubscriptionTier::VipForex => 'warning',
                        SubscriptionTier::VipCrypto => 'success',
                        SubscriptionTier::VipFull => 'primary',
                    }),
                Tables\Columns\TextColumn::make('vip_expires_at')
                    ->label('انقضای VIP')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('referral_code')
                    ->label('کد معرف')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('عضویت')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bot_language')
                    ->label('زبان')
                    ->options(collect(BotLanguage::cases())->mapWithKeys(
                        fn (BotLanguage $case) => [$case->value => $case->label()]
                    )),
                Tables\Filters\SelectFilter::make('subscription_tier')
                    ->label('اشتراک')
                    ->options(collect(SubscriptionTier::cases())->mapWithKeys(
                        fn (SubscriptionTier $case) => [$case->value => $case->label()]
                    )),
            ])
            ->actions([
                Tables\Actions\Action::make('grantVip')
                    ->label('فعال‌سازی / تغییر پلن VIP')
                    ->icon('heroicon-o-star')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('subscription_tier')
                            ->label('پلن')
                            ->options(collect(SubscriptionTier::cases())
                                ->filter(fn (SubscriptionTier $case) => $case->isVip())
                                ->mapWithKeys(fn (SubscriptionTier $case) => [$case->value => $case->label()]))
                            ->required(),
                        Forms\Components\TextInput::make('days')
                            ->label('تعداد روز')
                            ->numeric()
                            ->default(30)
                            ->required(),
                    ])
                    ->action(function (TelegramUser $record, array $data): void {
                        app(\App\Services\VipSubscriptionService::class)->activateVip(
                            $record,
                            SubscriptionTier::from($data['subscription_tier']),
                            (int) $data['days']
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('VIP فعال شد')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('extendVip')
                    ->label('تمدید VIP')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (TelegramUser $record): bool => $record->subscription_tier->isVip())
                    ->form([
                        Forms\Components\TextInput::make('days')
                            ->label('روزهای تمدید')
                            ->numeric()
                            ->default(30)
                            ->required()
                            ->helperText('به تاریخ انقضای فعلی اضافه می‌شود (اگر منقضی شده باشد از امروز حساب می‌شود).'),
                    ])
                    ->action(function (TelegramUser $record, array $data): void {
                        app(\App\Services\VipSubscriptionService::class)->extendVip(
                            $record,
                            (int) $data['days']
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('VIP تمدید شد')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make()->label('ویرایش'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف'),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelegramUsers::route('/'),
            'create' => Pages\CreateTelegramUser::route('/create'),
            'edit' => Pages\EditTelegramUser::route('/{record}/edit'),
        ];
    }
}
