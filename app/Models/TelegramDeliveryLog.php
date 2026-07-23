<?php

namespace App\Models;

use App\Enums\BotLanguage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TelegramDeliveryLog extends Model
{
    protected $fillable = [
        'context',
        'related_type',
        'related_id',
        'recipient_type',
        'chat_id',
        'telegram_user_id',
        'telegram_channel_id',
        'bot_language',
        'message_text',
        'image_path',
        'error_message',
        'status',
        'attempts',
        'last_attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'bot_language' => BotLanguage::class,
            'attempts' => 'integer',
            'last_attempted_at' => 'datetime',
        ];
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function telegramChannel(): BelongsTo
    {
        return $this->belongsTo(TelegramChannel::class);
    }

    public function markSent(): void
    {
        $this->update([
            'status' => 'sent',
            'last_attempted_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'attempts' => $this->attempts + 1,
            'last_attempted_at' => now(),
        ]);
    }
}
