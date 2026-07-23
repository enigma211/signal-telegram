<?php

namespace Tests\Feature;

use App\Enums\BotLanguage;
use App\Enums\SubscriptionTier;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\MessageTemplate;
use App\Models\PaymentSetting;
use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Services\BlockchainTxVerifier;
use App\Services\BotCopy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BotCopyAndChainVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bot_copy_uses_message_template(): void
    {
        MessageTemplate::query()->create([
            'key' => 'choose_menu',
            'name' => 'menu',
            'body_fa' => 'منوی تست',
            'body_en' => 'Test menu',
            'placeholders_help' => '',
        ]);

        $user = TelegramUser::query()->create([
            'telegram_id' => '55',
            'bot_language' => BotLanguage::Fa,
            'subscription_tier' => SubscriptionTier::Free,
            'referral_code' => 'MENUTEST1',
        ]);

        $text = app(BotCopy::class)->get('choose_menu', $user, [], 'fallback', 'fallback');
        $this->assertSame('منوی تست', $text);
    }

    public function test_english_landing_page_loads(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Nova Signal')
            ->assertSee('Start in Telegram')
            ->assertDontSee('USDT / month');
    }

    public function test_persian_landing_is_on_fa_path(): void
    {
        $this->get('/fa')
            ->assertOk()
            ->assertSee('نُوا سیگنال')
            ->assertSee('ورود به ربات')
            ->assertDontSee('USDT / ماه');
    }

    public function test_bep20_chain_verification_parses_transfer_log(): void
    {
        config(['services.blockchain.bscscan_api_key' => 'test-key']);

        $settings = PaymentSetting::current();
        $settings->update([
            'wallet_bep20' => '0x1111111111111111111111111111111111111111',
            'auto_confirm_verified_tx' => false,
        ]);

        $user = TelegramUser::query()->create([
            'telegram_id' => '77',
            'bot_language' => BotLanguage::En,
            'subscription_tier' => SubscriptionTier::Free,
            'referral_code' => 'CHAIN001',
        ]);

        $tx = Transaction::query()->create([
            'telegram_user_id' => $user->id,
            'amount' => 1,
            'currency' => 'USDT',
            'crypto_network' => 'BEP20',
            'tx_hash' => '0xabc123',
            'status' => TransactionStatus::Pending,
            'type' => TransactionType::Subscription,
            'subscription_tier' => SubscriptionTier::VipForex,
        ]);

        $toTopic = '0x'.str_pad(strtolower(ltrim($settings->wallet_bep20, '0x')), 64, '0', STR_PAD_LEFT);
        $amountHex = '0x0000000000000000000000000000000000000000000000000de0b6b3a7640000';

        Http::fake([
            'api.bscscan.com/*' => Http::response([
                'result' => [
                    'status' => '0x1',
                    'logs' => [[
                        'address' => BlockchainTxVerifier::USDT_BEP20,
                        'topics' => [
                            '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef',
                            '0x'.str_repeat('0', 64),
                            $toTopic,
                        ],
                        'data' => $amountHex,
                    ]],
                ],
            ]),
        ]);

        $result = app(BlockchainTxVerifier::class)->verify($tx);

        $this->assertTrue($result['ok']);
        $this->assertEqualsWithDelta(1.0, (float) $result['amount'], 0.02);
    }
}
