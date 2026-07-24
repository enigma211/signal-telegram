<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TwitterDeliveryLog extends Model
{
    protected $fillable = [
        'context',
        'related_type',
        'related_id',
        'message_text',
        'tweet_id',
        'error_message',
        'status',
        'attempts',
        'last_attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'last_attempted_at' => 'datetime',
        ];
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function markSent(?string $tweetId = null): void
    {
        $this->update([
            'status' => 'sent',
            'tweet_id' => $tweetId,
            'error_message' => null,
            'last_attempted_at' => now(),
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
