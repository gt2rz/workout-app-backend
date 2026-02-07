<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;

class EnsureApiKeyIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY');

        if (! $apiKey || ! ApiKey::findActive($apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or inactive API Key.',
            ], 401);
        }

        return $next($request);
    }
}
