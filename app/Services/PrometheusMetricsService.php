<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Prometheus Metrics Service
 * 
 * Collects application metrics in Prometheus format:
 * - HTTP request metrics
 * - Database query metrics
 * - Queue job metrics
 * - Cache hit/miss metrics
 * - Business metrics (messages sent, tickets created, etc)
 */
class PrometheusMetricsService
{
    private static $metricsStore = [];

    /**
     * Record HTTP request metric
     */
    public static function recordHttpRequest($method, $path, $statusCode, $duration)
    {
        $key = "http_request:{$method}:{$path}";
        
        self::incrementCounter($key, [
            'method' => $method,
            'path' => $path,
            'status' => $statusCode,
        ]);

        self::recordHistogram("http_request_duration_ms:{$method}:{$path}", $duration);

        if ($statusCode >= 400) {
            self::incrementCounter("http_errors:{$method}:{$path}:{$statusCode}");
        }
    }

    /**
     * Record database query metric
     */
    public static function recordDatabaseQuery($table, $operation, $duration)
    {
        $key = "db_query:{$table}:{$operation}";
        
        self::incrementCounter($key);
        self::recordHistogram("db_query_duration_ms:{$table}:{$operation}", $duration);

        if ($duration > 1000) {
            self::incrementCounter("db_slow_queries:{$table}:{$operation}");
        }
    }

    /**
     * Record queue job metric
     */
    public static function recordQueueJob($jobName, $duration, $success = true, $attempts = 1)
    {
        $status = $success ? 'success' : 'failed';
        
        self::incrementCounter("queue_job:{$jobName}:{$status}");
        self::recordHistogram("queue_job_duration_ms:{$jobName}", $duration);

        if ($attempts > 1) {
            self::incrementCounter("queue_job_retries:{$jobName}");
        }
    }

    /**
     * Record cache operation metric
     */
    public static function recordCacheOperation($operation, $hit = null)
    {
        if ($hit === true) {
            self::incrementCounter('cache_hits');
        } elseif ($hit === false) {
            self::incrementCounter('cache_misses');
        }
    }

    /**
     * Record business metric (domain-specific)
     */
    public static function recordBusinessMetric($metricName, $value = 1, $tags = [])
    {
        $key = "business:{$metricName}";
        self::incrementCounter($key, $tags, $value);
    }

    /**
     * Record WA message metric
     */
    public static function recordWaMessage($direction = 'outbound', $status = 'sent')
    {
        self::incrementCounter("wa_message:{$direction}:{$status}");
    }

    /**
     * Record queue ticket metric
     */
    public static function recordQueueTicket($type = 'ptsp', $action = 'created')
    {
        self::incrementCounter("queue_ticket:{$type}:{$action}");
    }

    /**
     * Record SIPP query metric
     */
    public static function recordSippQuery($duration)
    {
        self::recordHistogram('sipp_query_duration_ms', $duration);
    }

    /**
     * Get all metrics in Prometheus format
     */
    public static function getMetricsInPrometheusFormat()
    {
        $output = "# HELP lawangsewu_metrics Lawangsewu application metrics\n";
        $output .= "# TYPE lawangsewu_metrics gauge\n\n";

        foreach (self::$metricsStore as $metricName => $value) {
            $output .= "{$metricName} {$value}\n";
        }

        return $output;
    }

    /**
     * Get metrics summary for API/dashboard
     */
    public static function getMetricsSummary($timeWindow = 3600)
    {
        return [
            'http_requests' => self::getCounterValue('http_request_total', $timeWindow),
            'http_errors' => self::getCounterValue('http_errors_total', $timeWindow),
            'database_queries' => self::getCounterValue('db_query_total', $timeWindow),
            'database_slow_queries' => self::getCounterValue('db_slow_queries_total', $timeWindow),
            'queue_jobs_processed' => self::getCounterValue('queue_job:*:success', $timeWindow),
            'queue_jobs_failed' => self::getCounterValue('queue_job:*:failed', $timeWindow),
            'cache_hit_rate' => self::calculateCacheHitRate($timeWindow),
            'average_response_time_ms' => self::getAverageHistogramValue('http_request_duration_ms', $timeWindow),
            'average_db_query_time_ms' => self::getAverageHistogramValue('db_query_duration_ms', $timeWindow),
        ];
    }

    /**
     * Increment counter
     */
    private static function incrementCounter($key, $tags = [], $amount = 1)
    {
        $cacheKey = "prometheus:counter:{$key}";
        $current = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $current + $amount, 3600);
    }

    /**
     * Record histogram value
     */
    private static function recordHistogram($key, $value)
    {
        $cacheKey = "prometheus:histogram:{$key}";
        $values = Cache::get($cacheKey, []);
        $values[] = $value;
        
        // Keep only recent values (last 100 observations)
        if (count($values) > 100) {
            array_shift($values);
        }
        
        Cache::put($cacheKey, $values, 3600);
    }

    /**
     * Get counter value
     */
    private static function getCounterValue($key, $timeWindow = 3600)
    {
        $cacheKey = "prometheus:counter:{$key}";
        return Cache::get($cacheKey, 0);
    }

    /**
     * Get average histogram value
     */
    private static function getAverageHistogramValue($key, $timeWindow = 3600)
    {
        $cacheKey = "prometheus:histogram:{$key}";
        $values = Cache::get($cacheKey, []);
        
        if (empty($values)) {
            return 0;
        }

        return round(array_sum($values) / count($values), 2);
    }

    /**
     * Calculate cache hit rate
     */
    private static function calculateCacheHitRate($timeWindow = 3600)
    {
        $hits = self::getCounterValue('cache_hits', $timeWindow);
        $misses = self::getCounterValue('cache_misses', $timeWindow);
        $total = $hits + $misses;

        if ($total === 0) {
            return 0;
        }

        return round(($hits / $total) * 100, 2);
    }

    /**
     * Clear old metrics (run periodically)
     */
    public static function clearOldMetrics($ageHours = 24)
    {
        Log::info('Clearing old Prometheus metrics', ['age_hours' => $ageHours]);
        // Metrics are automatically expired from cache
    }

    /**
     * Export metrics to file for Prometheus scraping
     */
    public static function exportMetricsFile($filePath = null)
    {
        $filePath = $filePath ?? storage_path('prometheus/metrics.txt');
        
        $metrics = self::getMetricsInPrometheusFormat();
        file_put_contents($filePath, $metrics);

        Log::info('Prometheus metrics exported', ['file' => $filePath]);
    }
}
