<?php

namespace App\Features\Health\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * Verifica el estado de salud de la API y sus dependencias.
     */
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $dbStatus = 'connected';
        } catch (\Exception $e) {
            $dbStatus = 'disconnected';
            Log::error('Health Check Failure: '.$e->getMessage());
        }

        try {
            Redis::ping();
            $redisStatus = 'connected';
        } catch (\Exception $e) {
            $redisStatus = 'disconnected';
            Log::error('Health Check Failure (Redis): '.$e->getMessage());
        }

        return response()->json([
            'status' => $dbStatus === 'connected' ? 'up' : 'down',
            'data' => [
                'service' => 'Workout App API',
                'version' => config('api.version', '1.0.0'),
                'php_version' => PHP_VERSION,
                'environment' => config('app.env'),
                'checks' => [
                    'database' => $dbStatus,
                    'cache' => $redisStatus,
                ],
            ],
            'timestamp' => now()->toIso8601String(),
        ], $dbStatus === 'connected' ? 200 : 503);
    }
}
