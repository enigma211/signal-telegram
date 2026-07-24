<?php

namespace App\Services;

use App\Models\TwitterSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TwitterService
{
    public function settings(): TwitterSetting
    {
        return TwitterSetting::current();
    }

    public function enabled(): bool
    {
        return $this->settings()->isReady();
    }

    /**
     * Post a tweet. Optionally attach a local/public disk image.
     *
     * @return array{id: string, text: string}
     */
    public function post(string $text, ?string $imagePath = null): array
    {
        if (! $this->settings()->isConfigured()) {
            throw new RuntimeException('Twitter API credentials are missing.');
        }

        if (! $this->settings()->enabled) {
            throw new RuntimeException('Twitter posting is disabled.');
        }

        $text = $this->fitTweet($text);
        $payload = ['text' => $text];

        if (filled($imagePath)) {
            $mediaId = $this->uploadMedia($imagePath);
            if ($mediaId) {
                $payload['media'] = ['media_ids' => [$mediaId]];
            }
        }

        $url = 'https://api.twitter.com/2/tweets';
        $response = Http::withHeaders([
            'Authorization' => $this->oauthHeader('POST', $url),
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        if ($response->failed()) {
            Log::warning('Twitter API tweet failed', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);
            $response->throw();
        }

        $data = $response->json('data') ?? [];

        return [
            'id' => (string) ($data['id'] ?? ''),
            'text' => (string) ($data['text'] ?? $text),
        ];
    }

    public function toPlainText(string $markdown): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $markdown);
        $text = preg_replace('/\*([^*]+)\*/', '$1', $text) ?? $text;
        $text = preg_replace('/_([^_]+)_/', '$1', $text) ?? $text;
        $text = preg_replace('/`([^`]+)`/', '$1', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    public function fitTweet(string $text, int $limit = 280): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 1)).'…';
    }

    protected function uploadMedia(string $imagePath): ?string
    {
        $absolute = $this->resolveImagePath($imagePath);
        if (! $absolute || ! is_readable($absolute)) {
            return null;
        }

        $url = 'https://upload.twitter.com/1.1/media/upload.json';
        $mediaData = base64_encode((string) file_get_contents($absolute));
        $params = ['media_data' => $mediaData];

        try {
            $response = Http::asForm()
                ->withHeaders([
                    'Authorization' => $this->oauthHeader('POST', $url, $params),
                ])
                ->post($url, $params);

            if ($response->failed()) {
                Log::warning('Twitter media upload failed', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);

                return null;
            }

            $mediaId = $response->json('media_id_string') ?? $response->json('media_id');

            return $mediaId !== null ? (string) $mediaId : null;
        } catch (RequestException $e) {
            Log::warning('Twitter media upload exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    protected function resolveImagePath(string $imagePath): ?string
    {
        if (is_file($imagePath)) {
            return $imagePath;
        }

        $public = Storage::disk('public')->path($imagePath);
        if (is_file($public)) {
            return $public;
        }

        $local = storage_path('app/'.$imagePath);
        if (is_file($local)) {
            return $local;
        }

        return null;
    }

    /**
     * @param  array<string, string>  $extraOauthParams  params included in signature (e.g. form fields for media upload)
     */
    protected function oauthHeader(string $method, string $url, array $extraOauthParams = []): string
    {
        $settings = $this->settings();

        $oauth = [
            'oauth_consumer_key' => $settings->api_key,
            'oauth_nonce' => bin2hex(random_bytes(16)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_token' => $settings->access_token,
            'oauth_version' => '1.0',
        ];

        $signatureParams = array_merge($oauth, $extraOauthParams);
        ksort($signatureParams);

        $encoded = [];
        foreach ($signatureParams as $key => $value) {
            $encoded[] = rawurlencode((string) $key).'='.rawurlencode((string) $value);
        }

        $baseString = strtoupper($method)
            .'&'.rawurlencode($url)
            .'&'.rawurlencode(implode('&', $encoded));

        $signingKey = rawurlencode((string) $settings->api_secret)
            .'&'.rawurlencode((string) $settings->access_token_secret);

        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        $parts = [];
        foreach ($oauth as $key => $value) {
            $parts[] = rawurlencode($key).'="'.rawurlencode((string) $value).'"';
        }

        return 'OAuth '.implode(', ', $parts);
    }
}
