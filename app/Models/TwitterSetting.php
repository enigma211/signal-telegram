<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TwitterSetting extends Model
{
    protected $fillable = [
        'enabled',
        'api_key',
        'api_secret',
        'access_token',
        'access_token_secret',
        'post_signals',
        'post_results',
        'post_vip',
        'cta',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'post_signals' => 'boolean',
            'post_results' => 'boolean',
            'post_vip' => 'boolean',
            'api_key' => 'encrypted',
            'api_secret' => 'encrypted',
            'access_token' => 'encrypted',
            'access_token_secret' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('twitter_settings'));
        static::deleted(fn () => Cache::forget('twitter_settings'));
    }

    public static function current(): self
    {
        return Cache::remember('twitter_settings', now()->addMinutes(30), function () {
            return static::query()->first() ?? static::query()->create([
                'enabled' => (bool) config('services.twitter.enabled', false),
                'api_key' => config('services.twitter.api_key'),
                'api_secret' => config('services.twitter.api_secret'),
                'access_token' => config('services.twitter.access_token'),
                'access_token_secret' => config('services.twitter.access_token_secret'),
                'post_signals' => (bool) config('services.twitter.post_signals', true),
                'post_results' => (bool) config('services.twitter.post_results', true),
                'post_vip' => (bool) config('services.twitter.post_vip', false),
                'cta' => config('services.twitter.cta') ?: null,
            ]);
        });
    }

    public function isConfigured(): bool
    {
        return filled($this->api_key)
            && filled($this->api_secret)
            && filled($this->access_token)
            && filled($this->access_token_secret);
    }

    public function isReady(): bool
    {
        return $this->enabled && $this->isConfigured();
    }

    public function resolvedCta(): string
    {
        $cta = trim((string) $this->cta);
        if ($cta !== '') {
            return $cta;
        }

        $username = ltrim((string) config('services.telegram.bot_username_en', ''), '@');
        if ($username !== '' && $username !== 'YourEnBot') {
            return 'Start free: https://t.me/'.$username;
        }

        return '';
    }
}
