<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConditionalResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || ! $response instanceof JsonResponse) {
            return $response;
        }

        $etag = '"'.md5($response->getContent()).'"';
        $response->headers->set('ETag', $etag);
        $response->headers->set('Cache-Control', 'private, must-revalidate');

        if ($request->header('If-None-Match') === $etag) {
            return response()->noContent(304);
        }

        return $response;
    }
}
