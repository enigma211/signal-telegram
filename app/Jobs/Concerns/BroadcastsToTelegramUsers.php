<?php

namespace App\Jobs\Concerns;

use App\Enums\BotLanguage;
use App\Enums\MarketType;
use App\Models\TelegramChannel;
use App\Models\TelegramUser;
use App\Services\TelegramDeliveryLogger;
use App\Services\TelegramService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Throwable;

trait BroadcastsToTelegramUsers
{
    protected function telegramRateLimit(): int
    {
        return (int) config('services.telegram.broadcast_rate_per_second', 20);
    }

    protected function waitForTelegramSlot(BotLanguage|string $language): void
    {
        $lang = $language instanceof BotLanguage ? $language->value : $language;
        $key = 'telegram-broadcast-'.$lang;
        $maxAttempts = $this->telegramRateLimit();

        while (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            usleep(50_000);
        }

        RateLimiter::hit($key, 1);
    }

    protected function sendFormattedMessage(
        TelegramService $telegram,
        TelegramUser $user,
        string $text,
        ?string $imagePath = null,
        string $context = 'manual',
        ?Model $related = null
    ): void {
        $this->waitForTelegramSlot($user->bot_language);
        $logger = app(TelegramDeliveryLogger::class);
        $client = $telegram->forUser($user);

        try {
            if (filled($imagePath)) {
                $photo = $this->resolvePhotoPayload($imagePath);
                $response = $client->sendPhoto($user->telegram_id, $photo, $text);
            } else {
                $response = $client->sendMessage($user->telegram_id, $text);
            }

            $error = $logger->responseFailed($response);
            if ($error) {
                $logger->recordFailure(
                    $context,
                    (string) $user->telegram_id,
                    $text,
                    $error,
                    $user->bot_language,
                    $imagePath,
                    $user,
                    null,
                    $related
                );
            }
        } catch (Throwable $e) {
            $logger->recordFailure(
                $context,
                (string) $user->telegram_id,
                $text,
                $e->getMessage(),
                $user->bot_language,
                $imagePath,
                $user,
                null,
                $related
            );

            Log::warning('Failed to broadcast to Telegram user', [
                'context' => $context,
                'telegram_user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function broadcastToChannelsForLanguage(
        TelegramService $telegram,
        MarketType $market,
        BotLanguage $language,
        callable $messageBuilder,
        ?string $imagePath = null,
        string $context = 'manual',
        ?Model $related = null
    ): void {
        $logger = app(TelegramDeliveryLogger::class);
        $text = $messageBuilder();

        TelegramChannel::recipientsForMarket($market)
            ->whereHas('bot', fn ($q) => $q->where('language', $language->value)->where('is_active', true))
            ->with('bot')
            ->chunkById(50, function ($channels) use ($telegram, $language, $text, $imagePath, $context, $related, $logger): void {
                foreach ($channels as $channel) {
                    try {
                        $this->waitForTelegramSlot($language);
                        $client = $telegram->forLanguage($language);

                        if (filled($imagePath)) {
                            $response = $client->sendPhoto(
                                $channel->chat_id,
                                $this->resolvePhotoPayload($imagePath),
                                $text
                            );
                        } else {
                            $response = $client->sendMessage($channel->chat_id, $text);
                        }

                        $error = $logger->responseFailed($response);
                        if ($error) {
                            $logger->recordFailure(
                                $context,
                                (string) $channel->chat_id,
                                $text,
                                $error,
                                $language,
                                $imagePath,
                                null,
                                $channel,
                                $related
                            );
                        }
                    } catch (Throwable $e) {
                        $logger->recordFailure(
                            $context,
                            (string) $channel->chat_id,
                            $text,
                            $e->getMessage(),
                            $language,
                            $imagePath,
                            null,
                            $channel,
                            $related
                        );

                        Log::warning('Failed to broadcast to Telegram channel', [
                            'channel_id' => $channel->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }

    protected function resolvePhotoPayload(string $imagePath): string
    {
        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }

        if (Storage::disk('public')->exists($imagePath)) {
            return Storage::disk('public')->path($imagePath);
        }

        return $imagePath;
    }
}
