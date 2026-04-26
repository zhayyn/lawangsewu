# Performance Monitoring Guide

## Overview

Comprehensive performance monitoring system including request tracking, query analysis, and resource utilization.

## Request Timing Middleware

Create middleware to track request duration:

```php
// app/Http/Middleware/LogRequestPerformance.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequestPerformance {
    public function handle(Request $request, Closure $next) {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = (microtime(true) - $start) * 1000; // milliseconds
        
        // Log slow requests
        if ($duration > config('performance.slow_request_threshold', 500)) {
            Log::channel('performance')->warning('Slow request detected', [
                'path' => $request->path(),
                'method' => $request->method(),
                'duration_ms' => round($duration, 2),
                'threshold_ms' => config('performance.slow_request_threshold'),
                'user_id' => auth()->id(),
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        // Add timing header
        $response->header('X-Response-Time-Ms', round($duration, 2));
        
        return $response;
    }
}
```

Register in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(\App\Http\Middleware\LogRequestPerformance::class);
})
```

## Query Performance Monitoring

Enable in `AppServiceProvider`:

```php
public function boot() {
    DB::listen(function ($query) {
        $duration = $query->time; // milliseconds
        
        if ($duration > config('performance.slow_query_threshold', 1000)) {
            Log::channel('performance')->warning('Slow query', [
                'sql' => $query->sql,
                'duration_ms' => $duration,
                'bindings' => $query->bindings,
                'threshold_ms' => config('performance.slow_query_threshold'),
            ]);
        }
    });
}
```

## Memory Usage Tracking

```php
class MemoryTracker {
    private $startMemory;
    private $peakMemory;

    public function start() {
        $this->startMemory = memory_get_usage(true);
    }

    public function getUsage() {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        
        return [
            'current_mb' => round($current / 1024 / 1024, 2),
            'peak_mb' => round($peak / 1024 / 1024, 2),
            'used_mb' => round(($current - $this->startMemory) / 1024 / 1024, 2),
            'limit_mb' => round(ini_get('memory_limit') / 1024 / 1024, 2),
        ];
    }

    public function logUsage($context = []) {
        Log::channel('performance')->info('Memory usage', array_merge(
            $this->getUsage(),
            $context
        ));
    }
}
```

Usage:
```php
$tracker = new MemoryTracker();
$tracker->start();

// Heavy operation
$users = User::with('messages', 'conversations')->get();

$tracker->logUsage(['operation' => 'user_load']);
```

## Database Metrics

Create a service to collect database metrics:

```php
// app/Services/DatabaseMetricsService.php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class DatabaseMetricsService {
    public function getMetrics() {
        return [
            'connections' => [
                'active' => $this->getActiveConnections(),
                'total' => $this->getTotalConnections(),
                'max' => config('database.max_connections', 150),
            ],
            'queries' => [
                'total_today' => $this->getTotalQueriesToday(),
                'slow_queries' => $this->getSlowQueriesToday(),
                'failed_queries' => $this->getFailedQueriesToday(),
            ],
            'replication' => [
                'lag_seconds' => $this->getReplicationLag(),
                'is_healthy' => $this->isReplicationHealthy(),
            ],
        ];
    }

    private function getActiveConnections() {
        try {
            $result = DB::select('SHOW PROCESSLIST');
            return count($result);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getTotalConnections() {
        // Read from performance_schema if available
        try {
            $result = DB::select(
                'SELECT COUNT(*) as total FROM information_schema.PROCESSLIST'
            );
            return $result[0]->total ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getTotalQueriesToday() {
        // From custom query logging
        return \Cache::get('db_metrics.queries_today', 0);
    }

    private function getSlowQueriesToday() {
        return \Log::getMonolog()
            ->getHandlers()[0]
            ->getRecords()
            ->filter(fn($r) => $r['level'] >= 300) // Warning level
            ->count();
    }

    private function getFailedQueriesToday() {
        return \Cache::get('db_metrics.failed_queries', 0);
    }

    private function getReplicationLag() {
        try {
            if (config('database.replication.enabled')) {
                $result = DB::connection('replica')
                    ->select('SHOW SLAVE STATUS');
                return $result[0]->Seconds_Behind_Master ?? null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    private function isReplicationHealthy() {
        $lag = $this->getReplicationLag();
        return $lag !== null && $lag < 10; // Less than 10 seconds lag
    }
}
```

## Cache Efficiency

```php
class CacheMetrics {
    public function trackCacheHit($key, $hit = true) {
        $stats = \Cache::get('cache_stats', [
            'hits' => 0,
            'misses' => 0,
        ]);

        if ($hit) {
            $stats['hits']++;
        } else {
            $stats['misses']++;
        }

        \Cache::put('cache_stats', $stats, 3600);
    }

    public function getEfficiency() {
        $stats = \Cache::get('cache_stats', ['hits' => 0, 'misses' => 0]);
        $total = $stats['hits'] + $stats['misses'];
        
        return $total > 0 ? ($stats['hits'] / $total) * 100 : 0;
    }

    public function logMetrics() {
        Log::channel('performance')->info('Cache efficiency', [
            'hit_rate_percent' => round($this->getEfficiency(), 2),
            'hits' => \Cache::get('cache_stats.hits', 0),
            'misses' => \Cache::get('cache_stats.misses', 0),
        ]);
    }
}
```

## API Response Time Tracking

```php
class ApiResponseMetrics {
    public function trackResponse($path, $method, $duration, $statusCode) {
        $key = "api_metrics.{$path}.{$method}";
        
        $metrics = \Cache::get($key, [
            'total_requests' => 0,
            'total_time_ms' => 0,
            'min_time_ms' => PHP_FLOAT_MAX,
            'max_time_ms' => 0,
            'error_count' => 0,
        ]);

        $metrics['total_requests']++;
        $metrics['total_time_ms'] += $duration;
        $metrics['min_time_ms'] = min($metrics['min_time_ms'], $duration);
        $metrics['max_time_ms'] = max($metrics['max_time_ms'], $duration);
        
        if ($statusCode >= 400) {
            $metrics['error_count']++;
        }

        \Cache::put($key, $metrics, 3600);
    }

    public function getAverageResponseTime($path, $method) {
        $key = "api_metrics.{$path}.{$method}";
        $metrics = \Cache::get($key);
        
        if (!$metrics || $metrics['total_requests'] == 0) {
            return null;
        }

        return $metrics['total_time_ms'] / $metrics['total_requests'];
    }
}
```

## Monitoring Dashboard Endpoint

```php
// app/Http/Controllers/MetricsController.php
class MetricsController extends Controller {
    public function __construct(
        private DatabaseMetricsService $dbMetrics,
        private CacheMetrics $cacheMetrics,
    ) {}

    public function index() {
        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'server' => [
                'memory' => [
                    'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                    'limit_mb' => round(ini_get('memory_limit') / 1024 / 1024, 2),
                ],
                'uptime_seconds' => $this->getServerUptime(),
                'cpu_load' => $this->getCpuLoad(),
            ],
            'database' => $this->dbMetrics->getMetrics(),
            'cache' => [
                'hit_rate_percent' => round($this->cacheMetrics->getEfficiency(), 2),
                'driver' => config('cache.default'),
            ],
            'application' => [
                'total_users' => \App\Models\User::count(),
                'active_conversations' => \App\Models\Conversation::where('status', 'open')->count(),
                'pending_messages' => \App\Models\ChatMessage::where('wa_status', 'pending')->count(),
            ],
        ]);
    }

    private function getServerUptime() {
        if (function_exists('shell_exec')) {
            $uptime = shell_exec('uptime -s 2>/dev/null');
            return $uptime ? trim($uptime) : 'unknown';
        }
        return 'unknown';
    }

    private function getCpuLoad() {
        if (function_exists('sys_getloadavg')) {
            $loads = sys_getloadavg();
            return [
                '1_minute' => round($loads[0], 2),
                '5_minute' => round($loads[1], 2),
                '15_minute' => round($loads[2], 2),
            ];
        }
        return null;
    }
}
```

Register route in `routes/api.php`:
```php
Route::get('/metrics', [MetricsController::class, 'index'])
    ->middleware('auth:api');
```

## Configuration

Create `config/performance.php`:

```php
return [
    'slow_request_threshold' => env('SLOW_REQUEST_THRESHOLD_MS', 500),
    'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD_MS', 1000),
    'track_memory' => env('TRACK_MEMORY', false),
    'track_cache' => env('TRACK_CACHE', true),
    'performance_log_channel' => 'performance',
];
```

## Alerts

Send alerts for performance issues:

```php
class PerformanceAlertService {
    public static function checkAndAlert() {
        // Check database metrics
        $metrics = app(DatabaseMetricsService::class)->getMetrics();
        
        if ($metrics['connections']['active'] > $metrics['connections']['max'] * 0.8) {
            Notification::route('slack', config('services.slack.alert_webhook'))
                ->notify(new HighDatabaseConnectionAlert($metrics));
        }

        // Check cache efficiency
        $cacheHitRate = app(CacheMetrics::class)->getEfficiency();
        if ($cacheHitRate < 50) {
            Log::warning('Low cache hit rate', ['hit_rate' => $cacheHitRate]);
        }
    }
}
```

Run periodically:
```bash
php artisan schedule:add "App\Console\Commands\CheckPerformanceMetrics"
```

## Profiling

Use Laravel Debugbar in development:

```bash
composer require barryvdh/laravel-debugbar --dev
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

## Best Practices

- [ ] Monitor response times regularly
- [ ] Track slow queries
- [ ] Monitor memory usage
- [ ] Check database connections
- [ ] Track cache efficiency
- [ ] Set up performance alerts
- [ ] Review metrics daily
- [ ] Optimize based on data
- [ ] Use caching effectively
- [ ] Index frequently queried columns
- [ ] Profile before optimizing
- [ ] Document performance baselines
- [ ] Monitor third-party services
- [ ] Track API response times
- [ ] Archive old performance logs

## Files Reference

- `config/performance.php` - Performance configuration
- `app/Http/Middleware/LogRequestPerformance.php` - Request logging
- `app/Services/DatabaseMetricsService.php` - Database metrics
- `app/Http/Controllers/MetricsController.php` - Metrics endpoint
- `storage/logs/performance.log` - Performance logs
