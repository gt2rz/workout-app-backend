<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->secure() && !app()->isLocal()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Secure connection (HTTPS) is required.'
            ], 403);
        }

        return $next($request);
    }
}
