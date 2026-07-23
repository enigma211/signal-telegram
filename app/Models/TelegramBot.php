<?php

namespace App\Models;

use App\Enums\BotLanguage;
use App\Enums\MarketType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class TelegramBot extends Model
{
    protected $fillable = [
        'name',
        'language',
        'bot_token',
        'bot_username',
        'is_active',
        'webhook_set_at',
    ];

    protected function casts(): array
    {
        return [
            'language' => BotLanguage::class,
            'bot_token' => 'encrypted',
            'is_active' => 'boolean',
            'webhook_set_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $forget = function (TelegramBot $bot): void {
            Cache::forget('telegram_bot:'.$bot->language->value);
            Cache::forget('telegram_bot_username:'.$bot->language->value);
        };

        static::saved($forget);
        static::deleted($forget);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(TelegramChannel::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function findActiveByLanguage(BotLanguage|string $language): ?self
    {
        $value = $language instanceof BotLanguage ? $language->value : $language;

        return Cache::remember(
            "telegram_bot:{$value}",
            now()->addMinutes(30),
            fn () => static::query()
                ->active()
                ->where('language', $value)
                ->first()
        );
    }

    public function setBotUsernameAttribute(?string $value): void
    {
        $this->attributes['bot_username'] = $value ? ltrim($value, '@') : null;
    }

    public function usernameForLink(): ?string
    {
        if (blank($this->bot_username)) {
            return null;
        }

        return ltrim($this->bot_username, '@');
    }
}
