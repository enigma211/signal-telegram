<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTelegramWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.telegram.webhook_secret');

        if (blank($expected)) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Telegram webhook secret is not configured.');
        }

        $provided = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (! is_string($provided) || ! hash_equals((string) $expected, $provided)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid webhook secret.');
        }

        return $next($request);
    }
}
