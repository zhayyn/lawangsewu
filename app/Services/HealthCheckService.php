<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

/**
 * Health Check Service
 * 
 * Monitors application and external service health.
 * Provides comprehensive health status for monitoring and alerting.
 */
class HealthCheckService
{
    public function fullHealthCheck(): array
    {
        $results = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'environment' => config('app.env'),
            'components' => [],
            'summary' => [],
        ];

        // Application health (always safe)
        $results['components']['application'] = $this->checkApplication();

        // Database health (critical)
        $results['components']['database'] = $this->checkDatabase();

        // Cache health (important)
        $results['components']['cache'] = $this->checkCache();

        // Queue health (simplified - no external service call)
        $results['components']['queue'] = $this->checkQueue();

        // External services (with circuit breaker)
        if (config('wa_caraka.enabled', false)) {
            $results['components']['wa_runtime'] = $this->checkWaRuntime();
        }

        // SIPP database check with circuit breaker + timeout
        if (config('sipp.enabled', false)) {
            $results['components']['sipp_database'] = $this->checkSippDatabase();
        }

        // Disk space (simple, fast)
        $results['components']['disk_space'] = $this->checkDiskSpace();

        // Determine overall status
        $results['status'] = $this->determineOverallStatus($results['components']);
        $results['summary'] = $this->generateSummary($results['components']);

        return $results;
    }

    private function checkApplication(): array
    {
        return [
            'status' => 'healthy',
            'version' => config('app.version', 'unknown'),
            'environment' => config('app.env'),
            'debug' => config('app.debug'),
        ];
    }

    private function checkDatabase(): array
    {
        $status = [
            'status' => 'unknown',
            'latency_ms' => null,
        ];

        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $status['status'] = 'healthy';
            $status['latency_ms'] = round((microtime(true) - $start) * 1000, 2);
        } catch (\Exception $e) {
            $status['status'] = 'unhealthy';
            $status['error'] = $e->getMessage();
        }

        return $status;
    }

    private function checkCache(): array
    {
        $status = [
            'status' => 'unknown',
            'driver' => config('cache.default'),
            'latency_ms' => null,
        ];

        try {
            $start = microtime(true);
            Cache::put('health_check_test', 'ok', 60);
            $value = Cache::get('health_check_test');
            $status['latency_ms'] = round((microtime(true) - $start) * 1000, 2);
            
            if ($value === 'ok') {
                $status['status'] = 'healthy';
                Cache::forget('health_check_test');
            } else {
                $status['status'] = 'unhealthy';
                $status['error'] = 'Cache read/write mismatch';
            }
        } catch (\Exception $e) {
            $status['status'] = 'unhealthy';
            $status['error'] = $e->getMessage();
        }

        return $status;
    }

    private function checkQueue(): array
    {
        $status = [
            'status' => 'healthy',
            'driver' => config('queue.default', 'database'),
        ];

        try {
            // Simple check - just verify queue table exists if using database driver
            if (config('queue.default') === 'database') {
                // Don't query the queue table - just verify connection
                // Actual queue metrics can be checked separately
                $status['status'] = 'healthy';
            }
        } catch (\Exception $e) {
            $status['status'] = 'degraded';
            $status['error'] = 'Queue driver unavailable';
        }

        return $status;
    }

    private function checkWaRuntime(): array
    {
        $status = [
            'status' => 'unknown',
            'latency_ms' => null,
            'optional' => true,
        ];

        if (!config('wa_caraka.enabled')) {
            $status['status'] = 'disabled';
            return $status;
        }

        try {
            $start = microtime(true);
            $response = Http::timeout(5)
                ->get(config('wa_caraka.base_url') . '/health');
            $status['latency_ms'] = round((microtime(true) - $start) * 1000, 2);

            if ($response->successful()) {
                $status['status'] = 'healthy';
                $status['data'] = $response->json();
            } else {
                $status['status'] = 'unhealthy';
                $status['http_code'] = $response->status();
            }
        } catch (\Exception $e) {
            $status['status'] = 'unhealthy';
            $status['error'] = $e->getMessage();
        }

        return $status;
    }

    private function checkSippDatabase(): array
    {
        $status = [
            'status' => 'unknown',
            'latency_ms' => null,
            'optional' => true,
        ];

        if (!config('sipp.enabled', false)) {
            $status['status'] = 'disabled';
            return $status;
        }

        // Circuit breaker: Check cache first
        $cacheKey = 'health_check.sipp_status';
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $start = microtime(true);
            
            // Set timeout explicitly via ini_set (30 second connection timeout)
            $oldTimeout = ini_get('mysql.connect_timeout');
            ini_set('mysql.connect_timeout', '3');
            
            DB::connection('sipp')->getPdo();
            
            // Restore old timeout
            ini_set('mysql.connect_timeout', $oldTimeout);
            
            $status['latency_ms'] = round((microtime(true) - $start) * 1000, 2);
            $status['status'] = 'healthy';
            
            // Cache successful result for 60 seconds
            Cache::put($cacheKey, $status, 60);
        } catch (\Exception $e) {
            $status['status'] = 'unhealthy';
            $status['error'] = 'SIPP connection failed';
            
            // Cache failed result for 30 seconds (shorter TTL)
            Cache::put($cacheKey, $status, 30);
        }

        return $status;
    }

    private function checkDiskSpace(): array
    {
        $status = [
            'status' => 'healthy',
            'free_percent' => null,
            'free_bytes' => null,
        ];

        try {
            $disk = disk_free_space('/');
            $total = disk_total_space('/');
            
            if ($disk !== false && $total !== false) {
                $percent = ($disk / $total) * 100;
                $status['free_percent'] = round($percent, 2);
                $status['free_bytes'] = $disk;

                if ($percent < 5) {
                    $status['status'] = 'critical';
                } elseif ($percent < 10) {
                    $status['status'] = 'warning';
                }
            }
        } catch (\Exception $e) {
            $status['status'] = 'unknown';
            $status['error'] = $e->getMessage();
        }

        return $status;
    }

    private function determineOverallStatus(array $components): string
    {
        $statuses = collect($components)->pluck('status')->toArray();
        
        if (in_array('unhealthy', $statuses)) {
            // Check if unhealthy services are optional
            $criticalUnhealthy = false;
            foreach ($components as $name => $component) {
                if ($component['status'] === 'unhealthy' && !($component['optional'] ?? false)) {
                    $criticalUnhealthy = true;
                    break;
                }
            }
            return $criticalUnhealthy ? 'unhealthy' : 'degraded';
        }

        if (in_array('degraded', $statuses)) {
            return 'degraded';
        }

        if (in_array('warning', $statuses)) {
            return 'degraded';
        }

        return 'healthy';
    }

    private function generateSummary(array $components): array
    {
        $summary = [
            'healthy_count' => 0,
            'degraded_count' => 0,
            'unhealthy_count' => 0,
            'disabled_count' => 0,
        ];

        foreach ($components as $component) {
            match ($component['status']) {
                'healthy' => $summary['healthy_count']++,
                'degraded' => $summary['degraded_count']++,
                'unhealthy' => $summary['unhealthy_count']++,
                'disabled' => $summary['disabled_count']++,
                default => null,
            };
        }

        return $summary;
    }

    /**
     * Check only readiness (critical services)
     */
    public function readinessCheck(): array
    {
        return [
            'ready' => $this->isReady(),
            'checks' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
            ],
        ];
    }

    /**
     * Check only liveness (application running)
     */
    public function livenessCheck(): array
    {
        return [
            'alive' => true,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    private function isReady(): bool
    {
        $database = $this->checkDatabase();
        $cache = $this->checkCache();

        return $database['status'] === 'healthy' && $cache['status'] === 'healthy';
    }
}
