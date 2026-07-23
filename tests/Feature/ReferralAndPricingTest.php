<?php

namespace Tests\Feature;

use App\Enums\BotLanguage;
use App\Enums\SubscriptionTier;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Services\VipSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralAndPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_invite_url_uses_start_deep_link(): void
    {
        config(['services.telegram.bot_username_fa' => 'NovaSignalFaBot']);

        $user = TelegramUser::query()->create([
            'telegram_id' => '1001',
            'bot_language' => BotLanguage::Fa,
            'subscription_tier' => SubscriptionTier::Free,
            'referral_code' => 'ABCD1234',
        ]);

        $this->assertSame(
            'https://t.me/NovaSignalFaBot?start=ABCD1234',
            $user->referralInviteUrl()
        );
    }

    public function test_mark_referral_reward_paid(): void
    {
        $user = TelegramUser::query()->create([
            'telegram_id' => '2002',
            'bot_language' => BotLanguage::En,
            'subscription_tier' => SubscriptionTier::Free,
            'referral_code' => 'ENREF001',
            'crypto_wallet_address' => 'TWalletAddressExample',
        ]);

        $reward = Transaction::query()->create([
            'telegram_user_id' => $user->id,
            'amount' => 5.5,
            'currency' => 'USDT',
            'status' => TransactionStatus::Confirmed,
            'type' => TransactionType::ReferralReward,
            'admin_note' => 'Reward owed',
        ]);

        $paid = app(VipSubscriptionService::class)->markReferralRewardPaid(
            $reward,
            'txid-payout-1',
            'Sent via TRC20'
        );

        $this->assertSame(TransactionStatus::Paid, $paid->status);
        $this->assertSame('txid-payout-1', $paid->tx_hash);
        $this->assertStringContainsString('Sent via TRC20', (string) $paid->admin_note);
    }

    public function test_landing_shows_how_to_start_not_prices(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Start in Telegram')
            ->assertDontSee('USDT / month');

        $this->get('/fa')
            ->assertOk()
            ->assertSee('نُوا سیگنال')
            ->assertDontSee('USDT / ماه');
    }
}
