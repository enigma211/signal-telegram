<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'status',
        'subject',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['open', 'answered']);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public static function openOrCreateFor(TelegramUser $user): self
    {
        $ticket = static::query()
            ->where('telegram_user_id', $user->id)
            ->open()
            ->latest('id')
            ->first();

        if ($ticket) {
            return $ticket;
        }

        return static::query()->create([
            'telegram_user_id' => $user->id,
            'status' => 'open',
            'subject' => 'پشتیبانی ربات',
            'last_message_at' => now(),
        ]);
    }
}
