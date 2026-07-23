<?php

namespace App\Models;

use App\Enums\MarketType;
use App\Enums\SignalResult;
use App\Enums\SignalStatus;
use App\Enums\TargetAudience;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Signal extends Model
{
    use HasFactory;

    protected $fillable = [
        'market_type',
        'symbol',
        'entry_price',
        'tp1',
        'tp2',
        'tp3',
        'stop_loss',
        'image_path',
        'target_audience',
        'status',
        'result',
        'pips_gained',
    ];

    protected function casts(): array
    {
        return [
            'market_type' => MarketType::class,
            'target_audience' => TargetAudience::class,
            'status' => SignalStatus::class,
            'result' => SignalResult::class,
            'pips_gained' => 'integer',
        ];
    }

    public function updates(): HasMany
    {
        return $this->hasMany(SignalUpdate::class);
    }

    public function markAsBroadcasted(): void
    {
        $this->update(['status' => SignalStatus::Broadcasted]);
    }

    public function takeProfits(): array
    {
        return array_values(array_filter([
            'tp1' => $this->tp1,
            'tp2' => $this->tp2,
            'tp3' => $this->tp3,
        ], fn (?string $value): bool => filled($value)));
    }
}
