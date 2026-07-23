<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;
use Throwable;

class TransactionChainVerificationService
{
    public function __construct(
        protected BlockchainTxVerifier $verifier,
        protected VipSubscriptionService $vip,
        protected BotCopy $copy
    ) {}

    public function verifyPending(?int $limit = 30): int
    {
        $processed = 0;

        $pending = Transaction::query()
            ->where('type', TransactionType::Subscription->value)
            ->where('status', TransactionStatus::Pending->value)
            ->whereNotNull('tx_hash')
            ->whereNull('chain_verified_at')
            ->orderBy('id')
            ->limit($limit ?? 30)
            ->get();

        foreach ($pending as $transaction) {
            $this->verifyOne($transaction);
            $processed++;
        }

        return $processed;
    }

    public function verifyOne(Transaction $transaction): Transaction
    {
        $result = $this->verifier->verify($transaction);

        $transaction->update([
            'chain_verified_at' => $result['ok'] ? now() : null,
            'chain_verification_note' => $result['message'],
            'chain_verification_failed_at' => $result['ok'] ? null : now(),
        ]);

        if (! $result['ok']) {
            return $transaction->fresh();
        }

        $auto = (bool) ($this->vip->settings()->auto_confirm_verified_tx
            ?? config('services.blockchain.auto_confirm', true));

        if (! $auto) {
            return $transaction->fresh();
        }

        try {
            $confirmed = $this->vip->confirmSubscription($transaction->fresh());
            $user = $confirmed->telegramUser;

            if ($user) {
                $telegram = app(TelegramService::class)->forUser($user);
                $days = $this->vip->settings()->subscription_days;
                $plan = $user->subscription_tier->label();
                $text = $this->copy->get(
                    'payment_confirmed',
                    $user,
                    ['plan' => $plan, 'days' => (string) $days],
                    "✅ پرداخت تأیید شد.\nپلن *{plan}* برای {days} روز فعال شد.",
                    "✅ Payment confirmed.\n*{plan}* is active for {days} days."
                );
                $telegram->sendMessage($user->telegram_id, $text);
            }
        } catch (Throwable $e) {
            Log::error('Auto-confirm after chain verify failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $transaction->fresh();
    }
}
