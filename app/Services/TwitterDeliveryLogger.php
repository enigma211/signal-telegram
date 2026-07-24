<?php

namespace App\Services;

use App\Models\Signal;
use App\Models\TwitterDeliveryLog;
use Illuminate\Database\Eloquent\Model;

class TwitterDeliveryLogger
{
    public function start(string $context, string $messageText, ?Model $related = null): TwitterDeliveryLog
    {
        return TwitterDeliveryLog::query()->create([
            'context' => $context,
            'related_type' => $related ? $related::class : null,
            'related_id' => $related?->getKey(),
            'message_text' => $messageText,
            'status' => 'failed',
            'attempts' => 0,
            'last_attempted_at' => now(),
        ]);
    }

    public function recordFailure(
        string $context,
        string $error,
        string $messageText = '',
        ?Model $related = null
    ): TwitterDeliveryLog {
        return TwitterDeliveryLog::query()->create([
            'context' => $context,
            'related_type' => $related ? $related::class : null,
            'related_id' => $related?->getKey(),
            'message_text' => $messageText,
            'error_message' => $error,
            'status' => 'failed',
            'attempts' => 1,
            'last_attempted_at' => now(),
        ]);
    }
}
