<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.python_api.token');

        if (blank($expected)) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'API token is not configured.');
        }

        $provided = $request->bearerToken()
            ?? $request->header('X-API-Token');

        if (! is_string($provided) || ! hash_equals($expected, $provided)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid API token.');
        }

        return $next($request);
    }
}
