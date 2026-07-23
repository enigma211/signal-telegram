<?php

namespace App\Services;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\TelegramUser;
use App\Models\User;

class SupportService
{
    public function startSession(TelegramUser $user): SupportTicket
    {
        $ticket = SupportTicket::openOrCreateFor($user);

        $user->update([
            'bot_state' => 'await_support',
            'bot_state_payload' => ['ticket_id' => $ticket->id],
        ]);

        return $ticket;
    }

    public function endSession(TelegramUser $user): void
    {
        $user->update([
            'bot_state' => null,
            'bot_state_payload' => null,
        ]);
    }

    public function addUserMessage(TelegramUser $user, string $body): SupportMessage
    {
        $ticket = SupportTicket::openOrCreateFor($user);

        $message = SupportMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'sender' => 'user',
            'body' => trim($body),
        ]);

        $ticket->update([
            'status' => 'open',
            'last_message_at' => now(),
            'subject' => $ticket->subject ?: mb_substr(trim($body), 0, 80),
        ]);

        $user->update([
            'bot_state' => 'await_support',
            'bot_state_payload' => ['ticket_id' => $ticket->id],
        ]);

        return $message;
    }

    public function replyAsAdmin(SupportTicket $ticket, string $body, ?User $admin = null): SupportMessage
    {
        $message = SupportMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'sender' => 'admin',
            'admin_user_id' => $admin?->id,
            'body' => trim($body),
        ]);

        $ticket->update([
            'status' => 'answered',
            'last_message_at' => now(),
        ]);

        $user = $ticket->telegramUser;
        if ($user) {
            $telegram = app(TelegramService::class)->forUser($user);
            $text = app(BotCopy::class)->get(
                'support_admin_reply',
                $user,
                ['body' => $message->body],
                "💬 *پاسخ پشتیبانی:*\n{$message->body}",
                "💬 *Support reply:*\n{$message->body}"
            );

            $telegram->sendMessage(
                $user->telegram_id,
                $text,
                app(VipBotHandler::class)->menuKeyboard($user)
            );
        }

        return $message;
    }

    public function closeTicket(SupportTicket $ticket): void
    {
        $ticket->update(['status' => 'closed']);

        $user = $ticket->telegramUser;
        if ($user && $user->bot_state === 'await_support') {
            $this->endSession($user);
        }

        if ($user) {
            $telegram = app(TelegramService::class)->forUser($user);
            $text = app(BotCopy::class)->get(
                'support_closed',
                $user,
                [],
                '✅ تیکت پشتیبانی بسته شد. برای پیام جدید دوباره «پشتیبانی» را بزنید.',
                '✅ Support ticket closed. Tap Support again to start a new message.'
            );
            $telegram->sendMessage($user->telegram_id, $text, app(VipBotHandler::class)->menuKeyboard($user));
        }
    }
}
