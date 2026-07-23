<?php

namespace App\Models;

use App\Enums\BotLanguage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MessageTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'body_fa',
        'body_en',
        'placeholders_help',
    ];

    public static function findByKey(string $key): ?self
    {
        return Cache::remember(
            "message_template:{$key}",
            now()->addHour(),
            fn () => static::query()->where('key', $key)->first()
        );
    }

    public function bodyFor(BotLanguage|string $language): string
    {
        $value = $language instanceof BotLanguage ? $language : BotLanguage::from($language);

        return match ($value) {
            BotLanguage::Fa => $this->body_fa,
            BotLanguage::En => $this->body_en,
        };
    }

    public function render(BotLanguage|string $language, array $replacements): string
    {
        $body = $this->bodyFor($language);

        foreach ($replacements as $key => $value) {
            $body = str_replace('{'.$key.'}', (string) $value, $body);
        }

        // Remove leftover empty optional lines that only contained whitespace after replacement.
        return preg_replace("/\n{3,}/", "\n\n", trim($body)) ?? trim($body);
    }

    protected static function booted(): void
    {
        static::saved(function (MessageTemplate $template): void {
            Cache::forget("message_template:{$template->key}");
        });

        static::deleted(function (MessageTemplate $template): void {
            Cache::forget("message_template:{$template->key}");
        });
    }
}
