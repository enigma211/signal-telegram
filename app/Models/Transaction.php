<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\SubscriptionTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_user_id',
        'amount',
        'currency',
        'crypto_network',
        'tx_hash',
        'status',
        'type',
        'subscription_tier',
        'promo_code_id',
        'original_amount',
        'discount_percentage',
        'admin_note',
        'chain_verified_at',
        'chain_verification_failed_at',
        'chain_verification_note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'original_amount' => 'decimal:8',
            'discount_percentage' => 'integer',
            'status' => TransactionStatus::class,
            'type' => TransactionType::class,
            'subscription_tier' => SubscriptionTier::class,
            'chain_verified_at' => 'datetime',
            'chain_verification_failed_at' => 'datetime',
        ];
    }

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }
}
