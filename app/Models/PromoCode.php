<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_percentage',
        'expires_at',
        'max_uses',
        'current_uses',
    ];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'integer',
            'expires_at' => 'datetime',
            'max_uses' => 'integer',
            'current_uses' => 'integer',
        ];
    }

    public function isValid(): bool
    {
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return $this->current_uses < $this->max_uses;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->whereColumn('current_uses', '<', 'max_uses');
    }

    public function incrementUses(): void
    {
        $this->increment('current_uses');
    }
}
