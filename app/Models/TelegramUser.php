<?php

namespace App\Models;

use App\Enums\BotLanguage;
use App\Enums\MarketType;
use App\Enums\SubscriptionTier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TelegramUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_id',
        'first_name',
        'last_name',
        'username',
        'bot_language',
        'subscription_tier',
        'vip_expires_at',
        'vip_expiry_reminded_at',
        'referral_code',
        'referred_by',
        'crypto_wallet_address',
        'bot_state',
        'bot_state_payload',
    ];

    protected function casts(): array
    {
        return [
            'bot_language' => BotLanguage::class,
            'subscription_tier' => SubscriptionTier::class,
            'vip_expires_at' => 'datetime',
            'vip_expiry_reminded_at' => 'datetime',
            'bot_state_payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TelegramUser $user): void {
            if (empty($user->referral_code)) {
                $user->referral_code = static::generateUniqueReferralCode();
            }
        });
    }

    public static function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::query()->where('referral_code', $code)->exists());

        return $code;
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(TelegramUser::class, 'referred_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function displayName(): string
    {
        $name = trim(implode(' ', array_filter([(string) $this->first_name, (string) $this->last_name])));

        if ($name !== '' && filled($this->username)) {
            return $name.' (@'.$this->username.')';
        }

        if ($name !== '') {
            return $name;
        }

        if (filled($this->username)) {
            return '@'.$this->username;
        }

        return (string) $this->telegram_id;
    }

    public function referralInviteUrl(): string
    {
        $bot = TelegramBot::findActiveByLanguage($this->bot_language);
        $username = $bot?->usernameForLink()
            ?? ltrim((string) config('services.telegram.bot_username_'.$this->bot_language->value), '@');

        $username = ltrim($username ?: 'YourBot', '@');

        return 'https://t.me/'.$username.'?start='.$this->referral_code;
    }

    public function isVipActive(): bool
    {
        if ($this->subscription_tier === SubscriptionTier::Free) {
            return false;
        }

        return $this->vip_expires_at === null || $this->vip_expires_at->isFuture();
    }

    public function canReceiveMarket(MarketType $market): bool
    {
        if (! $this->isVipActive()) {
            return false;
        }

        return match ($market) {
            MarketType::Forex => $this->subscription_tier->hasForexAccess(),
            MarketType::Crypto => $this->subscription_tier->hasCryptoAccess(),
        };
    }

    public function scopeLanguage(Builder $query, BotLanguage|string $language): Builder
    {
        $value = $language instanceof BotLanguage ? $language->value : $language;

        return $query->where('bot_language', $value);
    }

    public function scopeActiveVip(Builder $query): Builder
    {
        return $query
            ->where('subscription_tier', '!=', SubscriptionTier::Free->value)
            ->where(function (Builder $q): void {
                $q->whereNull('vip_expires_at')
                    ->orWhere('vip_expires_at', '>', now());
            });
    }

    public function scopeEligibleForMarket(Builder $query, MarketType $market): Builder
    {
        $tiers = match ($market) {
            MarketType::Forex => [
                SubscriptionTier::VipForex->value,
                SubscriptionTier::VipFull->value,
            ],
            MarketType::Crypto => [
                SubscriptionTier::VipCrypto->value,
                SubscriptionTier::VipFull->value,
            ],
        };

        return $query
            ->whereIn('subscription_tier', $tiers)
            ->where(function (Builder $q): void {
                $q->whereNull('vip_expires_at')
                    ->orWhere('vip_expires_at', '>', now());
            });
    }
}
