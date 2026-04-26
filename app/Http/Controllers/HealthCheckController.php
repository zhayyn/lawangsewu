<?php

namespace App\Http\Controllers;

use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;

/**
 * Health Check Controller
 * 
 * Endpoints for monitoring application and external service health.
 * Used by load balancers, Kubernetes, and monitoring systems.
 */
class HealthCheckController extends Controller
{
    public function __construct(private HealthCheckService $healthCheck)
    {
    }

    /**
     * GET /health
     * 
     * Comprehensive health check including all services.
     * Returns 200 if healthy, 206 if degraded, 503 if unhealthy.
     */
    public function health(): JsonResponse
    {
        $result = $this->healthCheck->fullHealthCheck();

        $statusCode = match ($result['status']) {
            'healthy' => 200,
            'degraded' => 206,
            'unhealthy' => 503,
            default => 200,
        };

        return response()->json($result, $statusCode);
    }

    /**
     * GET /ready
     * 
     * Kubernetes readiness probe.
     * Checks if application is ready to receive requests.
     * Returns 200 if ready, 503 if not.
     */
    public function ready(): JsonResponse
    {
        $result = $this->healthCheck->readinessCheck();

        $statusCode = $result['ready'] ? 200 : 503;

        return response()->json($result, $statusCode);
    }

    /**
     * GET /live
     * 
     * Kubernetes liveness probe.
     * Checks if application process is alive.
     * Returns 200 if alive, never 503 (pod restart would indicate dead app).
     */
    public function live(): JsonResponse
    {
        $result = $this->healthCheck->livenessCheck();

        return response()->json($result, 200);
    }
}
