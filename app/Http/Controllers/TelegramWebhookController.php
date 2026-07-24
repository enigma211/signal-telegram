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
        $from = data_get($message, 'from', []);
        $telegramId = (string) data_get($from, 'id', $chatId);
        $text = trim((string) data_get($message, 'text', ''));
        $profile = $this->profileFromTelegramUser(is_array($from) ? $from : []);

        if ($chatId === null || $telegramId === '' || $text === '') {
            return;
        }

        if (str_starts_with($text, '/start')) {
            $parts = preg_split('/\s+/', $text, 2) ?: [];
            $referralCode = isset($parts[1]) ? trim($parts[1]) : null;
            $user = $telegram->registerSilently($telegramId, $botLanguage, $referralCode, $profile);

            $telegram->sendMessage(
                (string) $chatId,
                $telegram->welcomeText($user),
                $this->vipBot->menuKeyboard($user)
            );

            return;
        }

        $user = $telegram->registerSilently($telegramId, $botLanguage, null, $profile);
        $this->vipBot->handleText($user, (string) $chatId, $text);
    }

    protected function handleCallbackQuery(array $callbackQuery, BotLanguage $botLanguage): void
    {
        $callbackId = data_get($callbackQuery, 'id');
        $data = (string) data_get($callbackQuery, 'data', '');
        $chatId = data_get($callbackQuery, 'message.chat.id');
        $from = data_get($callbackQuery, 'from', []);
        $telegramId = (string) data_get($from, 'id');
        $profile = $this->profileFromTelegramUser(is_array($from) ? $from : []);

        if ($callbackId === null || $chatId === null || $telegramId === '') {
            return;
        }

        $user = $this->telegram->forLanguage($botLanguage)->registerSilently($telegramId, $botLanguage, null, $profile);
        $this->vipBot->handleCallback($user, (string) $chatId, (string) $callbackId, $data);
    }

    /**
     * @param  array<string, mixed>  $from
     * @return array{first_name: ?string, last_name: ?string, username: ?string}
     */
    protected function profileFromTelegramUser(array $from): array
    {
        return [
            'first_name' => isset($from['first_name']) ? (string) $from['first_name'] : null,
            'last_name' => isset($from['last_name']) ? (string) $from['last_name'] : null,
            'username' => isset($from['username']) ? (string) $from['username'] : null,
        ];
    }
}
