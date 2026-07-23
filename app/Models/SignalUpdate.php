<?php

namespace App\Models;

use App\Enums\BotLanguage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignalUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'signal_id',
        'update_message_fa',
        'update_message_en',
    ];

    public function signal(): BelongsTo
    {
        return $this->belongsTo(Signal::class);
    }

    public function messageFor(BotLanguage|string $language): string
    {
        $value = $language instanceof BotLanguage ? $language : BotLanguage::from($language);

        return match ($value) {
            BotLanguage::Fa => $this->update_message_fa,
            BotLanguage::En => $this->update_message_en,
        };
    }
}
