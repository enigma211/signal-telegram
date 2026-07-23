<?php

namespace App\Services;

use App\Enums\BotLanguage;
use App\Models\TelegramChannel;
use App\Models\TelegramDeliveryLog;
use App\Models\TelegramUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramDeliveryLogger
{
    public function recordFailure(
        string $context,
        string $chatId,
        string $messageText,
        string $error,
        ?BotLanguage $language = null,
        ?string $imagePath = null,
        ?TelegramUser $user = null,
        ?TelegramChannel $channel = null,
        ?Model $related = null
    ): TelegramDeliveryLog {
        return TelegramDeliveryLog::query()->create([
            'context' => $context,
            'related_type' => $related ? $related::class : null,
            'related_id' => $related?->getKey(),
            'recipient_type' => $channel ? 'channel' : 'user',
            'chat_id' => $chatId,
            'telegram_user_id' => $user?->id,
            'telegram_channel_id' => $channel?->id,
            'bot_language' => $language?->value ?? $user?->bot_language?->value,
            'message_text' => $messageText,
            'image_path' => $imagePath,
            'error_message' => $error,
            'status' => 'failed',
            'attempts' => 1,
            'last_attempted_at' => now(),
        ]);
    }

    public function responseFailed(?Response $response): ?string
    {
        if ($response === null) {
            return 'No response from Telegram API (missing token or request skipped).';
        }

        if ($response->successful() && data_get($response->json(), 'ok') === true) {
            return null;
        }

        return data_get($response->json(), 'description')
            ?? ('HTTP '.$response->status().': '.$response->body());
    }

    public function retry(TelegramDeliveryLog $log, TelegramService $telegram): bool
    {
        try {
            $language = $log->bot_language
                ?? $log->telegramUser?->bot_language
                ?? BotLanguage::Fa;

            $client = $telegram->forLanguage($language);
            $response = null;

            if (filled($log->image_path)) {
                $photo = $log->image_path;
                if (! str_starts_with($photo, 'http') && \Storage::disk('public')->exists($photo)) {
                    $photo = \Storage::disk('public')->path($photo);
                }
                $response = $client->sendPhoto($log->chat_id, $photo, $log->message_text);
            } else {
                $response = $client->sendMessage($log->chat_id, $log->message_text);
            }

            $error = $this->responseFailed($response);
            if ($error) {
                $log->markFailed($error);

                return false;
            }

            $log->markSent();

            return true;
        } catch (Throwable $e) {
            Log::error('Telegram delivery retry failed', [
                'log_id' => $log->id,
                'error' => $e->getMessage(),
            ]);
            $log->markFailed($e->getMessage());

            return false;
        }
    }
}
