<?php

namespace App\Services;

use App\Enums\BotLanguage;
use App\Models\TelegramBot;
use App\Models\TelegramUser;
use App\Enums\SubscriptionTier;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TelegramService
{
    protected ?BotLanguage $language = null;

    public function forLanguage(BotLanguage|string $language): static
    {
        $clone = clone $this;
        $clone->language = $language instanceof BotLanguage
            ? $language
            : BotLanguage::from($language);

        return $clone;
    }

    public function forUser(TelegramUser $user): static
    {
        return $this->forLanguage($user->bot_language);
    }

    public function language(): BotLanguage
    {
        if ($this->language === null) {
            throw new RuntimeException('Telegram bot language has not been set. Call forLanguage() first.');
        }

        return $this->language;
    }

    public function token(): string
    {
        $bot = TelegramBot::findActiveByLanguage($this->language());

        if ($bot && filled($bot->bot_token)) {
            return (string) $bot->bot_token;
        }

        $token = match ($this->language()) {
            BotLanguage::Fa => config('services.telegram.bot_token_fa'),
            BotLanguage::En => config('services.telegram.bot_token_en'),
        };

        if (blank($token)) {
            throw new RuntimeException(
                "Telegram bot token is missing for language [{$this->language()->value}]. Configure it in Filament → ربات‌های تلگرام."
            );
        }

        return (string) $token;
    }

    /**
     * Silent registration on /start — creates the user and assigns a referral code.
     */
    public function registerSilently(
        string $telegramId,
        BotLanguage|string $language,
        ?string $referralCode = null
    ): TelegramUser {
        $botLanguage = $language instanceof BotLanguage
            ? $language
            : BotLanguage::from($language);

        $user = TelegramUser::query()->firstOrCreate(
            ['telegram_id' => $telegramId],
            [
                'bot_language' => $botLanguage,
                'subscription_tier' => SubscriptionTier::Free,
            ]
        );

        if ($user->wasRecentlyCreated && filled($referralCode)) {
            $this->attachReferrer($user, $referralCode);
        }

        return $user->fresh();
    }

    public function attachReferrer(TelegramUser $user, string $referralCode): void
    {
        $code = strtoupper(trim($referralCode));

        $referrer = TelegramUser::query()
            ->where('referral_code', $code)
            ->where('id', '!=', $user->id)
            ->first();

        if ($referrer) {
            $user->update(['referred_by' => $referrer->id]);
        }
    }

    public function welcomeText(TelegramUser $user): string
    {
        $status = app(VipSubscriptionService::class)->statusText($user);

        return app(BotCopy::class)->get(
            'welcome',
            $user,
            ['status' => $status],
            "سلام! به ربات سیگنال هوش مصنوعی خوش آمدید.\n\n{$status}\n\nدر حالت رایگان، بخشی از سیگنال‌ها (نمونه تبلیغاتی) برای همه ارسال می‌شود.\nبا خرید VIP به سیگنال‌های بیشتر و کامل دسترسی دارید.",
            "Welcome to the AI Signal Bot!\n\n{$status}\n\nOn the free plan you still receive selected public/promo signals.\nVIP unlocks more signals and full access."
        );
    }

    public function mainKeyboard(TelegramUser $user): array
    {
        return app(VipBotHandler::class)->menuKeyboard($user);
    }

    public function sendMessage(
        string|int $chatId,
        string $text,
        ?array $replyMarkup = null,
        string $parseMode = 'Markdown'
    ): ?Response {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if ($replyMarkup !== null && ! empty($replyMarkup['inline_keyboard'])) {
            $payload['reply_markup'] = $replyMarkup;
        }

        return $this->request('sendMessage', $payload);
    }

    public function sendPhoto(
        string|int $chatId,
        string $photo,
        ?string $caption = null,
        ?array $replyMarkup = null,
        string $parseMode = 'Markdown'
    ): ?Response {
        try {
            $token = $this->token();
        } catch (RuntimeException $e) {
            Log::warning($e->getMessage());

            return null;
        }

        $payload = [
            'chat_id' => $chatId,
            'parse_mode' => $parseMode,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        if ($replyMarkup !== null && ! empty($replyMarkup['inline_keyboard'])) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        $isRemote = str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://');
        $isLocalFile = ! $isRemote && is_file($photo);

        if ($isLocalFile) {
            $response = Http::timeout(30)
                ->attach('photo', file_get_contents($photo), basename($photo))
                ->post("https://api.telegram.org/bot{$token}/sendPhoto", $payload);
        } else {
            $payload['photo'] = $photo;
            $response = Http::timeout(15)
                ->post("https://api.telegram.org/bot{$token}/sendPhoto", $payload);
        }

        if ($response->failed()) {
            Log::error('Telegram API request failed', [
                'method' => 'sendPhoto',
                'language' => $this->language()->value,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);
        }

        return $response;
    }

    public function answerCallbackQuery(
        string $callbackQueryId,
        ?string $text = null,
        bool $showAlert = false
    ): ?Response {
        $payload = [
            'callback_query_id' => $callbackQueryId,
            'show_alert' => $showAlert,
        ];

        if ($text !== null) {
            $payload['text'] = $text;
        }

        return $this->request('answerCallbackQuery', $payload);
    }

    public function request(string $method, array $payload = []): ?Response
    {
        try {
            $token = $this->token();
        } catch (RuntimeException $e) {
            Log::warning($e->getMessage());

            return null;
        }

        $response = Http::timeout(15)
            ->post("https://api.telegram.org/bot{$token}/{$method}", $payload);

        if ($response->failed()) {
            Log::error('Telegram API request failed', [
                'method' => $method,
                'language' => $this->language()->value,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);
        }

        return $response;
    }
}
