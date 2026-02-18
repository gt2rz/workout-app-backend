<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    private const CACHE_TTL = 86400; // 24 hours

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') || $request->isMethod('DELETE')) {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        if (! $idempotencyKey) {
            return $next($request);
        }

        $cacheKey = "idempotency:{$idempotencyKey}";

        $cached = Cache::get($cacheKey);

        if ($cached) {
            return response()->json(
                $cached['body'],
                $cached['status']
            )->withHeaders($cached['headers'] ?? []);
        }

        $response = $next($request);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 500) {
            Cache::put($cacheKey, [
                'body' => json_decode($response->getContent(), true),
                'status' => $response->getStatusCode(),
                'headers' => ['Idempotency-Replay' => 'false'],
            ], self::CACHE_TTL);
        }

        return $response;
    }
}
