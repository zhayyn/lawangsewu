<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Distributed Request Tracing Service
 * 
 * Implements request correlation IDs for cross-service tracing:
 * - Generates unique trace IDs per request
 * - Correlates logs across services
 * - Tracks request flow through application
 * - Integrates with monitoring systems (Prometheus, Jaeger, etc)
 */
class DistributedTracingService
{
    private static $traceId = null;
    private static $spanId = null;

    /**
     * Initialize trace for incoming request
     */
    public static function initializeTrace($request = null)
    {
        // Check if trace ID provided in header (from upstream service)
        $traceId = $request?->header('X-Trace-ID') ?? Str::ulid();
        $spanId = $request?->header('X-Span-ID') ?? Str::ulid();
        $parentSpanId = $request?->header('X-Parent-Span-ID');

        self::$traceId = $traceId;
        self::$spanId = $spanId;

        // Add to context for all logging
        Log::withContext([
            'trace_id' => $traceId,
            'span_id' => $spanId,
            'parent_span_id' => $parentSpanId,
        ]);

        return [
            'trace_id' => $traceId,
            'span_id' => $spanId,
            'parent_span_id' => $parentSpanId,
        ];
    }

    /**
     * Get current trace ID
     */
    public static function getTraceId()
    {
        return self::$traceId ?? Str::ulid();
    }

    /**
     * Create new span for operation
     */
    public static function startSpan($operationName, $attributes = [])
    {
        $spanId = Str::ulid();
        $parentSpanId = self::$spanId;

        Log::info('Span started', array_merge([
            'operation' => $operationName,
            'trace_id' => self::$traceId,
            'span_id' => $spanId,
            'parent_span_id' => $parentSpanId,
            'start_time' => microtime(true),
        ], $attributes));

        return new DistributedSpan($spanId, $operationName);
    }

    /**
     * Create child HTTP request with trace headers
     */
    public static function createTraceHeaders()
    {
        return [
            'X-Trace-ID' => self::$traceId,
            'X-Span-ID' => self::$spanId,
            'X-Parent-Span-ID' => self::$spanId,
        ];
    }

    /**
     * Track external service call
     */
    public static function trackServiceCall($serviceName, $method, $url, $duration, $statusCode = null, $error = null)
    {
        Log::info('Service call', [
            'service' => $serviceName,
            'method' => $method,
            'url' => $url,
            'duration_ms' => round($duration * 1000, 2),
            'status_code' => $statusCode,
            'error' => $error,
            'trace_id' => self::$traceId,
            'span_id' => self::$spanId,
        ]);
    }

    /**
     * Track database query in trace
     */
    public static function trackDatabaseQuery($table, $operation, $duration, $rowCount = null, $query = null)
    {
        if ($duration > 100) { // Log slow queries
            Log::warning('Slow database operation', [
                'table' => $table,
                'operation' => $operation,
                'duration_ms' => round($duration, 2),
                'row_count' => $rowCount,
                'query' => substr($query, 0, 200),
                'trace_id' => self::$traceId,
            ]);
        }
    }

    /**
     * Log error with full trace context
     */
    public static function logError($errorMessage, $exception = null, $context = [])
    {
        Log::error($errorMessage, array_merge([
            'error' => $exception?->getMessage(),
            'error_code' => $exception?->getCode(),
            'file' => $exception?->getFile(),
            'line' => $exception?->getLine(),
            'trace' => $exception?->getTraceAsString(),
            'trace_id' => self::$traceId,
            'span_id' => self::$spanId,
        ], $context));
    }
}

/**
 * Distributed Span Helper Class
 */
class DistributedSpan
{
    private $spanId;
    private $operationName;
    private $startTime;
    private $attributes = [];

    public function __construct($spanId, $operationName)
    {
        $this->spanId = $spanId;
        $this->operationName = $operationName;
        $this->startTime = microtime(true);
    }

    /**
     * Add attribute to span
     */
    public function addAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * End span and log duration
     */
    public function end($statusCode = 'OK', $error = null)
    {
        $duration = (microtime(true) - $this->startTime) * 1000; // ms

        Log::info('Span completed', array_merge([
            'operation' => $this->operationName,
            'span_id' => $this->spanId,
            'duration_ms' => round($duration, 2),
            'status_code' => $statusCode,
            'error' => $error,
        ], $this->attributes));

        return $duration;
    }
}
