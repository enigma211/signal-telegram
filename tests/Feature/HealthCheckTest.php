<?php

namespace Tests\Feature;

use App\Models\TwitterSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_json_with_checks(): void
    {
        TwitterSetting::current();

        $response = $this->getJson('/up');

        $response->assertJsonStructure([
            'status',
            'checks' => [
                'app',
                'nginx',
                'database',
                'redis',
                'horizon',
            ],
            'errors' => [
                'telegram_failed_24h',
                'twitter_failed_24h',
            ],
        ]);

        // In testing, Horizon masters are usually empty → 503 is acceptable
        $this->assertContains($response->status(), [200, 503]);
        $this->assertSame('ok', $response->json('checks.app.status'));
        $this->assertSame('ok', $response->json('checks.database.status'));
    }

    public function test_health_reports_redis_status(): void
    {
        TwitterSetting::current();

        try {
            Redis::connection()->ping();
            $redisAvailable = true;
        } catch (\Throwable) {
            $redisAvailable = false;
        }

        $response = $this->getJson('/up');
        $redisStatus = $response->json('checks.redis.status');

        if ($redisAvailable) {
            $this->assertSame('ok', $redisStatus);
        } else {
            $this->assertSame('error', $redisStatus);
        }
    }
}
