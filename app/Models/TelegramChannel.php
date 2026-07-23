<?php

namespace App\Models;

use App\Enums\MarketType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramChannel extends Model
{
    protected $fillable = [
        'telegram_bot_id',
        'title',
        'chat_id',
        'username',
        'market_type',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'market_type' => MarketType::class,
            'is_active' => 'boolean',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForMarket(Builder $query, MarketType $market): Builder
    {
        return $query->where(function (Builder $q) use ($market): void {
            $q->whereNull('market_type')
                ->orWhere('market_type', $market->value);
        });
    }

    public function setUsernameAttribute(?string $value): void
    {
        $this->attributes['username'] = $value ? ltrim($value, '@') : null;
    }

    public static function recipientsForMarket(MarketType $market)
    {
        return static::query()
            ->active()
            ->forMarket($market)
            ->whereHas('bot', fn (Builder $q) => $q->active())
            ->with('bot')
            ->orderBy('id');
    }
}
