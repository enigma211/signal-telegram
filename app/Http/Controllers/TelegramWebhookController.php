<?php

namespace App\Http\Controllers;

use App\Enums\BotLanguage;
use App\Services\TelegramService;
use App\Services\VipBotHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramService $telegram,
        protected VipBotHandler $vipBot
    ) {}

    public function __invoke(Request $request, string $language): JsonResponse
    {
        if (! in_array($language, ['fa', 'en'], true)) {
            abort(404);
        }

        $botLanguage = BotLanguage::from($language);
        $telegram = $this->telegram->forLanguage($botLanguage);
        $update = $request->all();

        try {
            if (isset($update['message'])) {
                $this->handleMessage($update['message'], $botLanguage, $telegram);
            }

            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query'], $botLanguage);
            }
        } catch (Throwable $e) {
            Log::error('Telegram webhook error', [
                'language' => $language,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    protected function handleMessage(
        array $message,
        BotLanguage $botLanguage,
        TelegramService $telegram
    ): void {
        $chatId = data_get($message, 'chat.id');
        $telegramId = (string) data_get($message, 'from.id', $chatId);
        $text = trim((string) data_get($message, 'text', ''));

        if ($chatId === null || $telegramId === '' || $text === '') {
            return;
        }

        if (str_starts_with($text, '/start')) {
            $parts = preg_split('/\s+/', $text, 2) ?: [];
            $referralCode = isset($parts[1]) ? trim($parts[1]) : null;
            $user = $telegram->registerSilently($telegramId, $botLanguage, $referralCode);

            $telegram->sendMessage(
                (string) $chatId,
                $telegram->welcomeText($user),
                $this->vipBot->menuKeyboard($user)
            );

            return;
        }

        $user = $telegram->registerSilently($telegramId, $botLanguage);
        $this->vipBot->handleText($user, (string) $chatId, $text);
    }

    protected function handleCallbackQuery(array $callbackQuery, BotLanguage $botLanguage): void
    {
        $callbackId = data_get($callbackQuery, 'id');
        $data = (string) data_get($callbackQuery, 'data', '');
        $chatId = data_get($callbackQuery, 'message.chat.id');
        $telegramId = (string) data_get($callbackQuery, 'from.id');

        if ($callbackId === null || $chatId === null || $telegramId === '') {
            return;
        }

        $user = $this->telegram->forLanguage($botLanguage)->registerSilently($telegramId, $botLanguage);
        $this->vipBot->handleCallback($user, (string) $chatId, (string) $callbackId, $data);
    }
}
