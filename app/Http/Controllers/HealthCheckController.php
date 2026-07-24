<?php

namespace App\Http\Controllers;

use App\Services\SystemHealthChecker;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    /**
     * Public health endpoint for UptimeRobot / monitoring.
     * Returns HTTP 200 when ok, 503 when a critical check fails.
     */
    public function __invoke(SystemHealthChecker $health): JsonResponse
    {
        $report = $health->check();
        $status = $report['status'] === 'ok' ? 200 : 503;

        return response()->json($report, $status);
    }
}
