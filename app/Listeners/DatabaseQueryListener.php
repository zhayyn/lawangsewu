<?php

namespace App\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use App\Services\DistributedTracingService;
use App\Services\PrometheusMetricsService;
use App\Services\DatabaseOptimizationService;
use Illuminate\Support\Facades\Log;

/**
 * Database Query Listener
 * 
 * Tracks all database queries for performance monitoring and optimization.
 * Logs slow queries and triggers alerts.
 */
class DatabaseQueryListener
{
    /**
     * Handle the event
     */
    public function handle(QueryExecuted $event)
    {
        // Get query duration in milliseconds
        $duration = $event->time;

        // Extract table name from SQL
        $table = $this->extractTableName($event->sql);

        // Record metrics
        PrometheusMetricsService::recordDatabaseQuery(
            $table,
            $this->extractOperation($event->sql),
            $duration
        );

        // Track with distributed tracing
        DistributedTracingService::trackDatabaseQuery(
            $event->sql,
            $event->bindings,
            $duration
        );

        // Log slow queries
        if ($duration > config('observability.performance.slow_query_threshold_ms')) {
            Log::warning('Slow database query detected', [
                'table' => $table,
                'duration_ms' => $duration,
                'threshold_ms' => config('observability.performance.slow_query_threshold_ms'),
                'sql' => substr($event->sql, 0, 200),
            ]);
        }

        // Monitor query performance
        DatabaseOptimizationService::monitorQueryPerformance(
            $event->sql,
            $event->bindings,
            $duration
        );
    }

    /**
     * Extract table name from SQL query
     */
    private function extractTableName($sql)
    {
        // Basic extraction - improve as needed
        if (preg_match('/FROM\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }
        if (preg_match('/INTO\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }
        if (preg_match('/UPDATE\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }
        return 'unknown';
    }

    /**
     * Extract operation type from SQL
     */
    private function extractOperation($sql)
    {
        $sql = strtoupper(trim($sql));
        if (str_starts_with($sql, 'SELECT')) return 'select';
        if (str_starts_with($sql, 'INSERT')) return 'insert';
        if (str_starts_with($sql, 'UPDATE')) return 'update';
        if (str_starts_with($sql, 'DELETE')) return 'delete';
        return 'other';
    }
}
