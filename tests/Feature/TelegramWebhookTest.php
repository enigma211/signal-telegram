<?php

namespace Tests\Feature;

use App\Enums\BotLanguage;
use App\Enums\SubscriptionTier;
use App\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token_fa' => 'FA-TEST-TOKEN',
            'services.telegram.bot_token_en' => 'EN-TEST-TOKEN',
            'services.telegram.webhook_secret' => 'test-webhook-secret',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => true]),
        ]);
    }

    protected function webhookHeaders(): array
    {
        return [
            'X-Telegram-Bot-Api-Secret-Token' => 'test-webhook-secret',
        ];
    }

    public function test_webhook_rejects_invalid_language(): void
    {
        $this->postJson('/api/telegram/webhook/de', [], $this->webhookHeaders())
            ->assertNotFound();
    }

    public function test_webhook_rejects_missing_secret(): void
    {
        $this->postJson('/api/telegram/webhook/fa', [
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 1, 'is_bot' => false, 'first_name' => 'X'],
                'chat' => ['id' => 1, 'type' => 'private'],
                'text' => '/start',
            ],
        ])->assertUnauthorized();
    }

    public function test_webhook_rejects_wrong_secret(): void
    {
        $this->postJson('/api/telegram/webhook/fa', [
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 1, 'is_bot' => false, 'first_name' => 'X'],
                'chat' => ['id' => 1, 'type' => 'private'],
                'text' => '/start',
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
        ])->assertUnauthorized();
    }

    public function test_start_registers_user_silently(): void
    {
        $payload = [
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 111222333, 'is_bot' => false, 'first_name' => 'Ali'],
                'chat' => ['id' => 111222333, 'type' => 'private'],
                'text' => '/start',
            ],
        ];

        $this->postJson('/api/telegram/webhook/fa', $payload, $this->webhookHeaders())
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('telegram_users', [
            'telegram_id' => '111222333',
            'bot_language' => BotLanguage::Fa->value,
            'subscription_tier' => SubscriptionTier::Free->value,
            'first_name' => 'Ali',
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'FA-TEST-TOKEN/sendMessage'));
    }

    public function test_start_stores_telegram_username_and_name(): void
    {
        $payload = [
            'message' => [
                'message_id' => 4,
                'from' => [
                    'id' => 555666777,
                    'is_bot' => false,
                    'first_name' => 'Ali',
                    'last_name' => 'Rezaei',
                    'username' => 'ali_trader',
                ],
                'chat' => ['id' => 555666777, 'type' => 'private'],
                'text' => '/start',
            ],
        ];

        $this->postJson('/api/telegram/webhook/fa', $payload, $this->webhookHeaders())->assertOk();

        $user = TelegramUser::query()->where('telegram_id', '555666777')->first();

        $this->assertNotNull($user);
        $this->assertSame('Ali', $user->first_name);
        $this->assertSame('Rezaei', $user->last_name);
        $this->assertSame('ali_trader', $user->username);
        $this->assertSame('Ali Rezaei (@ali_trader)', $user->displayName());
    }

    public function test_start_with_referral_code_attaches_referrer(): void
    {
        $referrer = TelegramUser::query()->create([
            'telegram_id' => '999888777',
            'bot_language' => BotLanguage::Fa,
            'subscription_tier' => SubscriptionTier::Free,
            'referral_code' => 'REFCODE1',
        ]);

        $payload = [
            'message' => [
                'message_id' => 2,
                'from' => ['id' => 444555666, 'is_bot' => false, 'first_name' => 'Sara'],
                'chat' => ['id' => 444555666, 'type' => 'private'],
                'text' => '/start REFCODE1',
            ],
        ];

        $this->postJson('/api/telegram/webhook/fa', $payload, $this->webhookHeaders())->assertOk();

        $this->assertDatabaseHas('telegram_users', [
            'telegram_id' => '444555666',
            'referred_by' => $referrer->id,
        ]);
    }

    public function test_start_syncs_bot_language_for_existing_user(): void
    {
        TelegramUser::query()->create([
            'telegram_id' => '777888999',
            'bot_language' => BotLanguage::En,
            'subscription_tier' => SubscriptionTier::Free,
            'referral_code' => 'OLDEN001',
        ]);

        $payload = [
            'message' => [
                'message_id' => 3,
                'from' => ['id' => 777888999, 'is_bot' => false, 'first_name' => 'Reza'],
                'chat' => ['id' => 777888999, 'type' => 'private'],
                'text' => '/start',
            ],
        ];

        $this->postJson('/api/telegram/webhook/fa', $payload, $this->webhookHeaders())->assertOk();

        $this->assertDatabaseHas('telegram_users', [
            'telegram_id' => '777888999',
            'bot_language' => BotLanguage::Fa->value,
        ]);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'FA-TEST-TOKEN/sendMessage')) {
                return false;
            }

            $body = $request->data();
            $text = (string) ($body['text'] ?? '');

            return str_contains($text, 'نُوا سیگنال') || str_contains($text, 'خوش آمدید');
        });
    }

    public function test_support_callback_starts_support_mode(): void
    {
        TelegramUser::query()->create([
            'telegram_id' => '123123123',
            'bot_language' => BotLanguage::En,
            'subscription_tier' => SubscriptionTier::Free,
            'referral_code' => 'ENUSER01',
        ]);

        $payload = [
            'callback_query' => [
                'id' => 'cb-1',
                'from' => ['id' => 123123123, 'is_bot' => false, 'first_name' => 'John'],
                'data' => 'menu:support',
                'message' => [
                    'message_id' => 10,
                    'chat' => ['id' => 123123123, 'type' => 'private'],
                ],
            ],
        ];

        $this->postJson('/api/telegram/webhook/en', $payload, $this->webhookHeaders())->assertOk();

        $this->assertDatabaseHas('telegram_users', [
            'telegram_id' => '123123123',
            'bot_state' => 'await_support',
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'EN-TEST-TOKEN/editMessageText'));
    }

    public function test_status_callback_edits_message_in_place(): void
    {
        TelegramUser::query()->create([
            'telegram_id' => '321321321',
            'bot_language' => BotLanguage::Fa,
            'subscription_tier' => SubscriptionTier::Free,
            'referral_code' => 'FAUSER01',
            'first_name' => 'Ali',
        ]);

        $payload = [
            'callback_query' => [
                'id' => 'cb-status-1',
                'from' => ['id' => 321321321, 'is_bot' => false, 'first_name' => 'Ali'],
                'data' => 'menu:status',
                'message' => [
                    'message_id' => 42,
                    'chat' => ['id' => 321321321, 'type' => 'private'],
                    'text' => 'old menu',
                ],
            ],
        ];

        $this->postJson('/api/telegram/webhook/fa', $payload, $this->webhookHeaders())->assertOk();

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'FA-TEST-TOKEN/editMessageText')) {
                return false;
            }

            $body = $request->data();

            return (int) ($body['message_id'] ?? 0) === 42
                && (string) ($body['chat_id'] ?? '') === '321321321';
        });

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'FA-TEST-TOKEN/sendMessage'));
    }

    public function test_blocked_user_cannot_use_bot(): void
    {
        TelegramUser::query()->create([
            'telegram_id' => '888777666',
            'bot_language' => BotLanguage::Fa,
            'subscription_tier' => SubscriptionTier::Free,
            'referral_code' => 'BLOCKED1',
            'is_blocked' => true,
            'blocked_at' => now(),
        ]);

        $payload = [
            'message' => [
                'message_id' => 99,
                'from' => ['id' => 888777666, 'is_bot' => false, 'first_name' => 'Blocked'],
                'chat' => ['id' => 888777666, 'type' => 'private'],
                'text' => '/start',
            ],
        ];

        $this->postJson('/api/telegram/webhook/fa', $payload, $this->webhookHeaders())->assertOk();

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'FA-TEST-TOKEN/sendMessage')) {
                return false;
            }

            $text = (string) ($request->data()['text'] ?? '');

            return str_contains($text, 'مسدود');
        });

        Http::assertNotSent(function ($request) {
            if (! str_contains($request->url(), 'FA-TEST-TOKEN/sendMessage')) {
                return false;
            }

            $text = (string) ($request->data()['text'] ?? '');

            return str_contains($text, 'نُوا سیگنال') || str_contains($text, 'خوش آمدید');
        });
    }
}
