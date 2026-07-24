<?php

namespace App\Filament\Resources;

use App\Enums\SubscriptionTier;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Filament\Resources\TransactionResource\Pages;
use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Services\TelegramService;
use App\Services\VipSubscriptionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Throwable;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'تراکنش‌ها';

    protected static ?string $modelLabel = 'تراکنش';

    protected static ?string $pluralModelLabel = 'تراکنش‌ها';

    protected static ?string $navigationGroup = 'کاربران و مالی';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = Transaction::query()
            ->where('type', TransactionType::ReferralReward->value)
            ->where('status', TransactionStatus::Confirmed->value)
            ->count();

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
                Forms\Components\Section::make('جزئیات تراکنش')->schema([
                    Forms\Components\Select::make('telegram_user_id')
                        ->label('کاربر تلگرام')
                        ->relationship('telegramUser', 'telegram_id')
                        ->getOptionLabelFromRecordUsing(fn (TelegramUser $record): string => $record->displayName().' · '.$record->telegram_id)
                        ->searchable(['telegram_id', 'username', 'first_name', 'last_name'])
                        ->preload()
                        ->required(),
                    Forms\Components\TextInput::make('amount')
                        ->label('مبلغ')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('original_amount')
                        ->label('مبلغ قبل از تخفیف')
                        ->numeric(),
                    Forms\Components\TextInput::make('currency')
                        ->label('ارز')
                        ->default('USDT')
                        ->required(),
                    Forms\Components\TextInput::make('crypto_network')
                        ->label('شبکه')
                        ->placeholder('TRC20 / BEP20'),
                    Forms\Components\TextInput::make('tx_hash')
                        ->label('هش تراکنش')
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->label('وضعیت')
                        ->options(collect(TransactionStatus::cases())->mapWithKeys(
                            fn (TransactionStatus $case) => [$case->value => $case->label()]
                        ))
                        ->required(),
                    Forms\Components\Select::make('type')
                        ->label('نوع')
                        ->options(collect(TransactionType::cases())->mapWithKeys(
                            fn (TransactionType $case) => [$case->value => $case->label()]
                        ))
                        ->required(),
                    Forms\Components\Select::make('subscription_tier')
                        ->label('پلن VIP')
                        ->options(collect(SubscriptionTier::cases())
                            ->filter(fn (SubscriptionTier $case) => $case->isVip())
                            ->mapWithKeys(fn (SubscriptionTier $case) => [$case->value => $case->label()]))
                        ->nullable(),
                    Forms\Components\Select::make('promo_code_id')
                        ->label('کد تخفیف')
                        ->relationship('promoCode', 'code')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\Textarea::make('admin_note')
                        ->label('یادداشت ادمین')
                        ->columnSpanFull(),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('telegramUser.crypto_wallet_address')
                    ->label('ولت معرف')
                    ->limit(16)
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')->label('ارز')->badge(),
                Tables\Columns\TextColumn::make('crypto_network')->label('شبکه')->placeholder('—'),
                Tables\Columns\TextColumn::make('subscription_tier')
                    ->label('پلن')
                    ->formatStateUsing(fn ($state) => $state instanceof SubscriptionTier ? $state->label() : ($state ?? '—')),
                Tables\Columns\TextColumn::make('tx_hash')
                    ->label('هش')
                    ->limit(14)
                    ->copyable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (TransactionStatus $state): string => $state->label())
                    ->color(fn (TransactionStatus $state): string => match ($state) {
                        TransactionStatus::Pending => 'warning',
                        TransactionStatus::Confirmed => 'success',
                        TransactionStatus::Failed => 'danger',
                        TransactionStatus::Paid => 'info',
                    }),
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->badge()
                    ->formatStateUsing(fn (TransactionType $state): string => $state->label()),
                Tables\Columns\TextColumn::make('chain_verified_at')
                    ->label('زنجیره')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(collect(TransactionStatus::cases())->mapWithKeys(
                        fn (TransactionStatus $case) => [$case->value => $case->label()]
                    )),
                Tables\Filters\SelectFilter::make('type')
                    ->label('نوع')
                    ->options(collect(TransactionType::cases())->mapWithKeys(
                        fn (TransactionType $case) => [$case->value => $case->label()]
                    )),
                Tables\Filters\Filter::make('unpaid_referrals')
                    ->label('پاداش معرفی پرداخت‌نشده')
                    ->query(fn ($query) => $query
                        ->where('type', TransactionType::ReferralReward->value)
                        ->where('status', TransactionStatus::Confirmed->value)),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('تأیید و فعال‌سازی VIP')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Transaction $record): bool => $record->type === TransactionType::Subscription
                        && $record->status === TransactionStatus::Pending)
                    ->action(function (Transaction $record): void {
                        try {
                            $vip = app(VipSubscriptionService::class);
                            $confirmed = $vip->confirmSubscription($record);
                            $user = $confirmed->telegramUser;

                            if ($user) {
                                $telegram = app(TelegramService::class)->forUser($user);
                                $days = $vip->settings()->subscription_days;
                                $plan = $user->subscription_tier->label();
                                $text = app(\App\Services\BotCopy::class)->get(
                                    'payment_confirmed',
                                    $user,
                                    ['plan' => $plan, 'days' => (string) $days],
                                    "✅ پرداخت تأیید شد.\nپلن *{$plan}* برای {$days} روز فعال شد.",
                                    "✅ Payment confirmed.\n*{$user->subscription_tier->name}* is active for {$days} days."
                                );
                                $telegram->sendMessage($user->telegram_id, $text);
                            }

                            Notification::make()->title('VIP فعال شد')->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title('خطا')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('verifyChain')
                    ->label('بررسی زنجیره')
                    ->icon('heroicon-o-shield-check')
                    ->color('gray')
                    ->visible(fn (Transaction $record): bool => $record->type === TransactionType::Subscription
                        && $record->status === TransactionStatus::Pending
                        && filled($record->tx_hash))
                    ->action(function (Transaction $record): void {
                        try {
                            $updated = app(\App\Services\TransactionChainVerificationService::class)->verifyOne($record);
                            Notification::make()
                                ->title($updated->chain_verified_at ? 'زنجیره تأیید شد' : 'تأیید نشد')
                                ->body((string) $updated->chain_verification_note)
                                ->color($updated->chain_verified_at ? 'success' : 'warning')
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()->title('خطا')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('markReferralPaid')
                    ->label('علامت پرداخت به معرف')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('ثبت پرداخت پاداش معرفی')
                    ->modalDescription(fn (Transaction $record): string => 'ولت کاربر: '
                        .($record->telegramUser?->crypto_wallet_address ?: 'ثبت نشده')
                        ."\nمبلغ: {$record->amount} {$record->currency}")
                    ->visible(fn (Transaction $record): bool => $record->type === TransactionType::ReferralReward
                        && $record->status === TransactionStatus::Confirmed)
                    ->form([
                        Forms\Components\TextInput::make('tx_hash')
                            ->label('هش واریز (اختیاری)')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('admin_note')
                            ->label('یادداشت')
                            ->placeholder('مثلاً: واریز TRC20 انجام شد'),
                    ])
                    ->action(function (Transaction $record, array $data): void {
                        try {
                            $paid = app(VipSubscriptionService::class)->markReferralRewardPaid(
                                $record,
                                $data['tx_hash'] ?? null,
                                $data['admin_note'] ?? null
                            );
                            $user = $paid->telegramUser;

                            if ($user) {
                                $telegram = app(TelegramService::class)->forUser($user);
                                $amount = $paid->amount.' '.$paid->currency;
                                $text = app(\App\Services\BotCopy::class)->get(
                                    'referral_paid',
                                    $user,
                                    ['amount' => $amount],
                                    "✅ پاداش معرفی *{$amount}* به ولت شما واریز شد.",
                                    "✅ Referral reward *{$amount}* has been paid to your wallet."
                                );
                                $telegram->sendMessage($user->telegram_id, $text);
                            }

                            Notification::make()->title('پاداش به‌عنوان پرداخت‌شده ثبت شد')->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title('خطا')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('رد کردن')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Transaction $record): bool => $record->status === TransactionStatus::Pending)
                    ->form([
                        Forms\Components\Textarea::make('admin_note')
                            ->label('دلیل رد')
                            ->required(),
                    ])
                    ->action(function (Transaction $record, array $data): void {
                        $rejected = app(VipSubscriptionService::class)->rejectSubscription($record, $data['admin_note']);
                        $user = $rejected->telegramUser;

                        if ($user) {
                            $telegram = app(TelegramService::class)->forUser($user);
                            $text = app(\App\Services\BotCopy::class)->get(
                                'payment_rejected',
                                $user,
                                ['reason' => $data['admin_note']],
                                "❌ پرداخت رد شد.\nدلیل: {$data['admin_note']}",
                                "❌ Payment rejected.\nReason: {$data['admin_note']}"
                            );
                            $telegram->sendMessage($user->telegram_id, $text);
                        }

                        Notification::make()->title('تراکنش رد شد')->warning()->send();
                    }),
                Tables\Actions\EditAction::make()->label('ویرایش'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markReferralPaidBulk')
                        ->label('پرداخت‌شده (پاداش معرفی)')
                        ->icon('heroicon-o-banknotes')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $vip = app(VipSubscriptionService::class);
                            $n = 0;
                            foreach ($records as $record) {
                                if (
                                    $record->type === TransactionType::ReferralReward
                                    && $record->status === TransactionStatus::Confirmed
                                ) {
                                    $vip->markReferralRewardPaid($record);
                                    $n++;
                                }
                            }
                            Notification::make()->title("{$n} پاداش علامت پرداخت‌شده خورد")->success()->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make()->label('حذف'),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
