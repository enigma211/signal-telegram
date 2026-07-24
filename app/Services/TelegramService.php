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
     *
     * @param  array{first_name?: ?string, last_name?: ?string, username?: ?string}|null  $profile
     */
    public function registerSilently(
        string $telegramId,
        BotLanguage|string $language,
        ?string $referralCode = null,
        ?array $profile = null
    ): TelegramUser {
        $botLanguage = $language instanceof BotLanguage
            ? $language
            : BotLanguage::from($language);

        $profileData = $this->normalizeProfile($profile);

        $user = TelegramUser::query()->firstOrCreate(
            ['telegram_id' => $telegramId],
            array_merge(
                [
                    'bot_language' => $botLanguage,
                    'subscription_tier' => SubscriptionTier::Free,
                ],
                $profileData
            )
        );

        $updates = [];

        if (! $user->wasRecentlyCreated && $user->bot_language !== $botLanguage) {
            $updates['bot_language'] = $botLanguage;
        }

        if ($profileData !== []) {
            foreach ($profileData as $key => $value) {
                if ($user->{$key} !== $value) {
                    $updates[$key] = $value;
                }
            }
        }

        if ($updates !== []) {
            $user->update($updates);
        }

        if ($user->wasRecentlyCreated && filled($referralCode)) {
            $this->attachReferrer($user, $referralCode);
        }

        return $user->fresh();
    }

    /**
     * @param  array{first_name?: ?string, last_name?: ?string, username?: ?string}|null  $profile
     * @return array{first_name?: ?string, last_name?: ?string, username?: ?string}
     */
    protected function normalizeProfile(?array $profile): array
    {
        if ($profile === null) {
            return [];
        }

        $data = [];

        if (array_key_exists('first_name', $profile)) {
            $data['first_name'] = filled($profile['first_name']) ? trim((string) $profile['first_name']) : null;
        }

        if (array_key_exists('last_name', $profile)) {
            $data['last_name'] = filled($profile['last_name']) ? trim((string) $profile['last_name']) : null;
        }

        if (array_key_exists('username', $profile)) {
            $username = filled($profile['username'])
                ? ltrim(trim((string) $profile['username']), '@')
                : null;
            $data['username'] = $username !== '' ? $username : null;
        }

        return $data;
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
            "به *نوا سیگنال* خوش آمدید 👋\n\nما با کمک هوش مصنوعی، بازارهای فارکس و ارز دیجیتال را تحلیل می‌کنیم و هر ساعت سیگنال‌های رایگان را برای شما ارسال می‌کنیم.\n\n{$status}\n\nاز منوی زیر گزینه موردنظرتان را انتخاب کنید.\nبا VIP به سیگنال‌های بیشتر و کامل دسترسی دارید.",
            "Welcome to *Nova Signal* 👋\n\nWe analyze Forex and Crypto markets with AI and send free signals to you every hour.\n\n{$status}\n\nChoose an option from the menu below.\nVIP unlocks more signals and full access."
        );
    }

    public function mainKeyboard(TelegramUser $user): array
    {
        return app(VipBotHandler::class)->menuKeyboard($user);
    }

    /**
     * Profile texts shown before the user presses Start (BotFather-style description).
     */
    public function syncPublicDescriptions(): void
    {
        $descriptions = [
            BotLanguage::Fa->value => [
                'description' => 'به نوا سیگنال خوش آمدید. ما با کمک هوش مصنوعی، سیگنال‌های فارکس و ارز دیجیتال را تحلیل می‌کنیم و هر ساعت به‌صورت رایگان برایتان ارسال می‌کنیم. برای شروع، Start را بزنید.',
                'short_description' => 'سیگنال رایگان فارکس و کریپتو با هوش مصنوعی — نوا سیگنال',
            ],
            BotLanguage::En->value => [
                'description' => 'Welcome to Nova Signal. We analyze Forex and Crypto markets with AI and send free signals every hour. Tap Start to begin.',
                'short_description' => 'Free Forex & Crypto AI signals — Nova Signal',
            ],
        ];

        foreach ($descriptions as $lang => $texts) {
            try {
                $bot = $this->forLanguage($lang);
                $bot->request('setMyDescription', [
                    'description' => $texts['description'],
                    'language_code' => $lang === 'fa' ? 'fa' : 'en',
                ]);
                $bot->request('setMyShortDescription', [
                    'short_description' => $texts['short_description'],
                    'language_code' => $lang === 'fa' ? 'fa' : 'en',
                ]);
                // Also set default (no language_code) for clients without locale match.
                $bot->request('setMyDescription', [
                    'description' => $texts['description'],
                ]);
                $bot->request('setMyShortDescription', [
                    'short_description' => $texts['short_description'],
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to sync Telegram bot description', [
                    'language' => $lang,
                    'error' => $e->getMessage(),
                ]);
            }
        }
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

    public function editMessageText(
        string|int $chatId,
        int $messageId,
        string $text,
        ?array $replyMarkup = null,
        string $parseMode = 'Markdown'
    ): ?Response {
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = ! empty($replyMarkup['inline_keyboard'])
                ? $replyMarkup
                : ['inline_keyboard' => []];
        }

        return $this->request('editMessageText', $payload);
    }

    /**
     * Edit an existing bot message when possible; otherwise send a new one.
     */
    public function respond(
        string|int $chatId,
        string $text,
        ?array $replyMarkup = null,
        ?int $messageId = null,
        string $parseMode = 'Markdown'
    ): ?Response {
        if ($messageId !== null) {
            $response = $this->editMessageText($chatId, $messageId, $text, $replyMarkup, $parseMode);

            if ($response !== null && $response->successful()) {
                return $response;
            }

            $description = (string) data_get($response?->json(), 'description', '');

            // Same content is fine for in-place UX; treat as success.
            if (str_contains($description, 'message is not modified')) {
                return $response;
            }
        }

        return $this->sendMessage($chatId, $text, $replyMarkup, $parseMode);
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
