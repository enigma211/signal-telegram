<?php

namespace App\Services;

use App\Models\TelegramDeliveryLog;
use App\Models\TwitterDeliveryLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Throwable;

class SystemHealthChecker
{
    /**
     * @return array{status: string, checks: array<string, array{status: string, message?: string}>}
     */
    public function check(): array
    {
        $checks = [
            'app' => ['status' => 'ok', 'message' => 'Application responding'],
            'nginx' => ['status' => 'ok', 'message' => 'Request reached PHP-FPM (Nginx up)'],
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'horizon' => $this->checkHorizon(),
        ];

        $ok = collect($checks)->every(fn (array $c): bool => $c['status'] === 'ok');

        return [
            'status' => $ok ? 'ok' : 'error',
            'checks' => $checks,
            'errors' => [
                'telegram_failed_24h' => TelegramDeliveryLog::query()
                    ->where('status', 'failed')
                    ->where('created_at', '>=', now()->subDay())
                    ->count(),
                'twitter_failed_24h' => TwitterDeliveryLog::query()
                    ->where('status', 'failed')
                    ->where('created_at', '>=', now()->subDay())
                    ->count(),
            ],
        ];
    }

    /**
     * @return array{status: string, message?: string}
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('select 1');

            return ['status' => 'ok', 'message' => 'MySQL connected'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, message?: string}
     */
    protected function checkRedis(): array
    {
        try {
            $pong = Redis::connection()->ping();
            $ok = $pong === true || $pong === 'PONG' || $pong === '+PONG';

            return $ok
                ? ['status' => 'ok', 'message' => 'Redis ping ok']
                : ['status' => 'error', 'message' => 'Unexpected Redis ping response'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, message?: string}
     */
    protected function checkHorizon(): array
    {
        try {
            if (! interface_exists(MasterSupervisorRepository::class)) {
                return ['status' => 'error', 'message' => 'Horizon not installed'];
            }

            $masters = app(MasterSupervisorRepository::class)->all();

            if (empty($masters)) {
                return [
                    'status' => 'error',
                    'message' => 'Horizon master supervisor not running',
                ];
            }

            $paused = collect($masters)->contains(fn ($m) => ($m->status ?? null) === 'paused');

            return [
                'status' => $paused ? 'error' : 'ok',
                'message' => $paused
                    ? 'Horizon is paused'
                    : 'Horizon running ('.count($masters).' master)',
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
