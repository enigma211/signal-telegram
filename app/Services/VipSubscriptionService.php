<?php

namespace App\Services;

use App\Enums\BotLanguage;
use App\Enums\SubscriptionTier;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\PaymentSetting;
use App\Models\PromoCode;
use App\Models\TelegramUser;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VipSubscriptionService
{
    public function settings(): PaymentSetting
    {
        return PaymentSetting::current();
    }

    public function priceForTier(SubscriptionTier $tier): float
    {
        $settings = $this->settings();

        return (float) match ($tier) {
            SubscriptionTier::VipForex => $settings->price_forex,
            SubscriptionTier::VipCrypto => $settings->price_crypto,
            SubscriptionTier::VipFull => $settings->price_full,
            default => throw new InvalidArgumentException('Invalid VIP tier.'),
        };
    }

    public function resolvePromo(?string $code): ?PromoCode
    {
        if (blank($code)) {
            return null;
        }

        $promo = PromoCode::query()
            ->where('code', strtoupper(trim($code)))
            ->first();

        if (! $promo || ! $promo->isValid()) {
            return null;
        }

        return $promo;
    }

    public function discountedAmount(float $amount, ?PromoCode $promo): float
    {
        if (! $promo) {
            return round($amount, 2);
        }

        $discounted = $amount * (1 - ($promo->discount_percentage / 100));

        return round(max($discounted, 0), 2);
    }

    public function activateVip(
        TelegramUser $user,
        SubscriptionTier $tier,
        ?int $days = null
    ): TelegramUser {
        if (! $tier->isVip()) {
            throw new InvalidArgumentException('Tier must be a VIP plan.');
        }

        $days ??= $this->settings()->subscription_days;
        $base = $user->isVipActive() && $user->vip_expires_at
            ? $user->vip_expires_at->copy()
            : now();

        $user->update([
            'subscription_tier' => $tier,
            'vip_expires_at' => $base->addDays($days),
            'vip_expiry_reminded_at' => null,
            'bot_state' => null,
            'bot_state_payload' => null,
        ]);

        return $user->fresh();
    }

    public function createPendingSubscription(
        TelegramUser $user,
        SubscriptionTier $tier,
        string $network,
        string $txHash,
        ?PromoCode $promo = null
    ): Transaction {
        $original = $this->priceForTier($tier);
        $amount = $this->discountedAmount($original, $promo);
        $hash = trim($txHash);

        if (Transaction::query()->where('tx_hash', $hash)->exists()) {
            throw new InvalidArgumentException(
                app(BotCopy::class)->get(
                    'tx_hash_duplicate',
                    $user,
                    [],
                    'این هش قبلاً ثبت شده است.',
                    'This transaction hash was already submitted.'
                )
            );
        }

        return DB::transaction(function () use ($user, $tier, $network, $hash, $promo, $original, $amount) {
            $transaction = Transaction::query()->create([
                'telegram_user_id' => $user->id,
                'amount' => $amount,
                'original_amount' => $original,
                'currency' => $this->settings()->currency,
                'crypto_network' => strtoupper($network),
                'tx_hash' => $hash,
                'status' => TransactionStatus::Pending,
                'type' => TransactionType::Subscription,
                'subscription_tier' => $tier->value,
                'promo_code_id' => $promo?->id,
                'discount_percentage' => $promo?->discount_percentage,
            ]);

            $user->update([
                'bot_state' => null,
                'bot_state_payload' => null,
            ]);

            \App\Jobs\VerifyTransactionJob::dispatch($transaction)->delay(now()->addSeconds(15));

            return $transaction;
        });
    }

    public function confirmSubscription(Transaction $transaction, ?string $adminNote = null): Transaction
    {
        if ($transaction->type !== TransactionType::Subscription) {
            throw new InvalidArgumentException('Only subscription transactions can activate VIP.');
        }

        if ($transaction->status === TransactionStatus::Confirmed) {
            return $transaction;
        }

        return DB::transaction(function () use ($transaction, $adminNote) {
            $user = $transaction->telegramUser()->lockForUpdate()->firstOrFail();
            $tier = SubscriptionTier::from($transaction->subscription_tier);

            $transaction->update([
                'status' => TransactionStatus::Confirmed,
                'admin_note' => $adminNote,
            ]);

            if ($transaction->promo_code_id) {
                PromoCode::query()->whereKey($transaction->promo_code_id)->increment('current_uses');
            }

            $this->activateVip($user, $tier);
            $this->grantReferralReward($transaction->fresh());

            return $transaction->fresh(['telegramUser']);
        });
    }

    public function rejectSubscription(Transaction $transaction, ?string $adminNote = null): Transaction
    {
        $transaction->update([
            'status' => TransactionStatus::Failed,
            'admin_note' => $adminNote,
        ]);

        return $transaction->fresh();
    }

    public function grantReferralReward(Transaction $transaction): ?Transaction
    {
        $buyer = $transaction->telegramUser;
        $referrer = $buyer?->referrer;

        if (! $buyer || ! $referrer) {
            return null;
        }

        $percent = $this->settings()->referral_percent;
        if ($percent <= 0) {
            return null;
        }

        $rewardAmount = round(((float) $transaction->amount) * ($percent / 100), 2);
        if ($rewardAmount <= 0) {
            return null;
        }

        return Transaction::query()->create([
            'telegram_user_id' => $referrer->id,
            'amount' => $rewardAmount,
            'currency' => $transaction->currency,
            'crypto_network' => $transaction->crypto_network,
            'tx_hash' => null,
            'status' => TransactionStatus::Confirmed,
            'type' => TransactionType::ReferralReward,
            'admin_note' => "Reward from user #{$buyer->id} transaction #{$transaction->id}",
        ]);
    }

    public function markReferralRewardPaid(Transaction $transaction, ?string $payoutTxHash = null, ?string $adminNote = null): Transaction
    {
        if ($transaction->type !== TransactionType::ReferralReward) {
            throw new InvalidArgumentException('Only referral rewards can be marked as paid.');
        }

        if ($transaction->status === TransactionStatus::Paid) {
            return $transaction;
        }

        if ($transaction->status !== TransactionStatus::Confirmed) {
            throw new InvalidArgumentException('Referral reward must be confirmed (owed) before marking paid.');
        }

        $note = trim(($transaction->admin_note ? $transaction->admin_note."\n" : '').($adminNote ?? 'Marked paid by admin'));

        $transaction->update([
            'status' => TransactionStatus::Paid,
            'tx_hash' => $payoutTxHash ?: $transaction->tx_hash,
            'admin_note' => $note,
        ]);

        return $transaction->fresh();
    }

    public function remindExpiringSubscriptions(): int
    {
        $days = max(1, (int) $this->settings()->vip_reminder_days);

        $users = TelegramUser::query()
            ->notBlocked()
            ->where('subscription_tier', '!=', SubscriptionTier::Free->value)
            ->whereNotNull('vip_expires_at')
            ->whereBetween('vip_expires_at', [now(), now()->addDays($days)->endOfDay()])
            ->whereNull('vip_expiry_reminded_at')
            ->get();

        $sent = 0;

        foreach ($users as $user) {
            try {
                $telegram = app(TelegramService::class)->forUser($user);
                $expiry = $user->vip_expires_at
                    ? ($user->bot_language === BotLanguage::Fa
                        ? jalali($user->vip_expires_at, 'Y/m/d H:i')
                        : $user->vip_expires_at->format('Y-m-d H:i'))
                    : null;
                $text = app(BotCopy::class)->get(
                    'vip_expiry_reminder',
                    $user,
                    ['expiry' => (string) $expiry],
                    "⚠️ اشتراک VIP شما تا *{$expiry}* منقضی می‌شود.\nبرای تمدید از منوی خرید VIP استفاده کنید.",
                    "⚠️ Your VIP expires on *{$expiry}*.\nUse Buy VIP to renew."
                );

                $telegram->sendMessage($user->telegram_id, $text);
                $user->update(['vip_expiry_reminded_at' => now()]);
                $sent++;
            } catch (\Throwable) {
                // ignore notify failures
            }
        }

        return $sent;
    }

    public function expireOverdueSubscriptions(bool $notify = true): int
    {
        $users = TelegramUser::query()
            ->notBlocked()
            ->where('subscription_tier', '!=', SubscriptionTier::Free->value)
            ->whereNotNull('vip_expires_at')
            ->where('vip_expires_at', '<=', now())
            ->get();

        foreach ($users as $user) {
            $user->update([
                'subscription_tier' => SubscriptionTier::Free,
            ]);

            if ($notify) {
                try {
                    $telegram = app(TelegramService::class)->forUser($user);
                    $text = app(BotCopy::class)->get(
                        'vip_expired',
                        $user,
                        [],
                        "⏱ اشتراک VIP شما منقضی شد.\nاکنون در حالت رایگان هستید و فقط سیگنال‌های عمومی را دریافت می‌کنید.\nبرای تمدید از منوی خرید VIP استفاده کنید.",
                        "⏱ Your VIP subscription has expired.\nYou are now on the free plan and only receive public signals.\nUse Buy VIP to renew."
                    );
                    $telegram->sendMessage($user->telegram_id, $text);
                } catch (\Throwable) {
                    // ignore notify failures during expiry sweep
                }
            }
        }

        return $users->count();
    }

    public function extendVip(TelegramUser $user, int $days, ?SubscriptionTier $tier = null): TelegramUser
    {
        $tier ??= $user->subscription_tier->isVip()
            ? $user->subscription_tier
            : SubscriptionTier::VipFull;

        return $this->activateVip($user, $tier, $days);
    }

    public function statusText(TelegramUser $user): string
    {
        $tier = $user->subscription_tier->label();
        $active = $user->isVipActive();
        $fa = $user->bot_language === BotLanguage::Fa;
        $expiry = $user->vip_expires_at
            ? ($fa
                ? jalali($user->vip_expires_at, 'Y/m/d H:i')
                : $user->vip_expires_at->format('Y-m-d H:i'))
            : ($fa ? 'نامحدود' : 'Unlimited');
        $state = $active
            ? ($fa ? 'فعال ✅' : 'Active ✅')
            : ($fa ? 'غیرفعال ❌' : 'Inactive ❌');
        $tierDisplay = $fa ? $tier : $user->subscription_tier->name;

        return app(BotCopy::class)->get(
            'status',
            $user,
            [
                'tier' => $tierDisplay,
                'state' => $state,
                'expiry' => $expiry,
                'referral_code' => $user->referral_code,
            ],
            "📊 *وضعیت اشتراک*\nسطح: *{$tierDisplay}*\nوضعیت: *{$state}*\nانقضا: `{$expiry}`\nکد معرف: `{$user->referral_code}`\n\nاز منوی زیر می‌توانید به داشبورد برگردید یا خرید VIP را شروع کنید.",
            "📊 *Subscription Status*\nPlan: *{$tierDisplay}*\nStatus: *{$state}*\nExpires: `{$expiry}`\nReferral code: `{$user->referral_code}`\n\nUse the menu below to return to Dashboard or buy VIP."
        );
    }

    protected function t(TelegramUser $user, string $fa, string $en): string
    {
        return $user->bot_language === BotLanguage::Fa ? $fa : $en;
    }
}
