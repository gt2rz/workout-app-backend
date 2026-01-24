<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

        // Lo ideal es que estas llaves estén en el .env o una tabla de 'apps'
        if ($apiKey !== config('app.api_key')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid API Key.'
            ], 401);
        }

        return $next($request);
    }
}
