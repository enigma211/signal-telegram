<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PaymentSetting extends Model
{
    protected $fillable = [
        'wallet_trc20',
        'wallet_bep20',
        'default_network',
        'price_forex',
        'price_crypto',
        'price_full',
        'subscription_days',
        'vip_reminder_days',
        'referral_percent',
        'auto_confirm_verified_tx',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'price_forex' => 'decimal:2',
            'price_crypto' => 'decimal:2',
            'price_full' => 'decimal:2',
            'subscription_days' => 'integer',
            'vip_reminder_days' => 'integer',
            'referral_percent' => 'integer',
            'auto_confirm_verified_tx' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('payment_settings'));
        static::deleted(fn () => Cache::forget('payment_settings'));
    }

    public static function current(): self
    {
        return Cache::remember('payment_settings', now()->addMinutes(30), function () {
            return static::query()->first() ?? static::query()->create([
                'price_forex' => config('services.telegram.pricing.forex', 49),
                'price_crypto' => config('services.telegram.pricing.crypto', 49),
                'price_full' => config('services.telegram.pricing.combo', 79),
                'subscription_days' => 30,
                'vip_reminder_days' => 3,
                'referral_percent' => 10,
                'auto_confirm_verified_tx' => true,
                'default_network' => 'TRC20',
                'currency' => 'USDT',
            ]);
        });
    }

    public function walletForNetwork(string $network): ?string
    {
        return match (strtoupper($network)) {
            'TRC20' => $this->wallet_trc20,
            'BEP20' => $this->wallet_bep20,
            default => $this->wallet_trc20 ?: $this->wallet_bep20,
        };
    }
}
