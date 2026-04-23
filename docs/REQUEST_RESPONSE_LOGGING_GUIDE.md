# Request/Response Logging Middleware Guide

## Overview

Comprehensive logging of all HTTP requests and responses for debugging, security auditing, and compliance.

## Request/Response Logging Middleware

Create comprehensive logging middleware:

```php
// app/Http/Middleware/LogRequestResponse.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequestResponse {
    private array $skipPaths = [
        'health',
        'ready',
        'live',
        'metrics',
    ];

    private array $maskFields = [
        'password',
        'token',
        'secret',
        'api_key',
        'credit_card',
        'phone',
    ];

    public function handle(Request $request, Closure $next) {
        // Skip logging for certain paths
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Log request
        $this->logRequest($request);

        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000;

        // Log response
        $this->logResponse($request, $response, $duration);

        return $response;
    }

    private function logRequest(Request $request) {
        Log::info('HTTP request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id' => auth()->id(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'query' => $this->maskSensitive($request->query()),
            'body' => $this->maskSensitive($request->except(['password', 'token'])),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function logResponse(Request $request, $response, $duration) {
        // Get response content
        $content = $response->getContent();
        $contentSize = strlen($content);

        Log::info('HTTP response', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
            'content_size_bytes' => $contentSize,
            'user_id' => auth()->id(),
            'response_headers' => $this->sanitizeHeaders($response->headers->all()),
            'timestamp' => now()->toIso8601String(),
        ]);

        // Log errors
        if ($response->getStatusCode() >= 400) {
            Log::warning('HTTP error response', [
                'method' => $request->method(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'user_id' => auth()->id(),
                'body' => $this->maskSensitive(json_decode($content, true) ?? $content),
            ]);
        }

        // Log slow responses
        if ($duration > config('logging.slow_request_threshold_ms', 500)) {
            Log::warning('Slow HTTP request', [
                'method' => $request->method(),
                'path' => $request->path(),
                'duration_ms' => round($duration, 2),
                'threshold_ms' => config('logging.slow_request_threshold_ms'),
            ]);
        }
    }

    private function sanitizeHeaders($headers) {
        $sensitive = ['authorization', 'cookie', 'x-api-key', 'x-token'];
        
        return collect($headers)
            ->mapWithKeys(function ($value, $key) use ($sensitive) {
                $lower = strtolower($key);
                if (in_array($lower, $sensitive)) {
                    return [$key => '***REDACTED***'];
                }
                return [$key => is_array($value) ? implode(',', $value) : $value];
            })
            ->toArray();
    }

    private function maskSensitive($data) {
        if (!is_array($data)) {
            return $data;
        }

        return collect($data)
            ->map(function ($value, $key) {
                if ($this->isSensitive($key)) {
                    return '***REDACTED***';
                }
                if (is_array($value)) {
                    return $this->maskSensitive($value);
                }
                return $value;
            })
            ->toArray();
    }

    private function isSensitive($key) {
        $lower = strtolower($key);
        foreach ($this->maskFields as $field) {
            if (strpos($lower, $field) !== false) {
                return true;
            }
        }
        return false;
    }

    private function shouldSkip(Request $request) {
        foreach ($this->skipPaths as $path) {
            if ($request->is($path) || $request->is($path . '/*')) {
                return true;
            }
        }
        return false;
    }
}
```

Register in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(\App\Http\Middleware\LogRequestResponse::class);
    $middleware->api(\App\Http\Middleware\LogRequestResponse::class);
})
```

## Structured Logging Channel

Create dedicated channel in `config/logging.php`:

```php
'channels' => [
    'requests' => [
        'driver' => 'daily',
        'path' => storage_path('logs/requests.log'),
        'level' => 'info',
        'days' => 14,
        'formatter' => \App\Logging\JsonFormatter::class,
    ],
    'errors' => [
        'driver' => 'daily',
        'path' => storage_path('logs/errors.log'),
        'level' => 'error',
        'days' => 30,
    ],
    'performance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/performance.log'),
        'level' => 'notice',
        'days' => 7,
    ],
],
```

## JSON Formatter

Create custom formatter for structured logs:

```php
// app/Logging/JsonFormatter.php
namespace App\Logging;

use Monolog\Formatter\FormatterInterface;

class JsonFormatter implements FormatterInterface {
    public function format(array $record) {
        $formatted = [
            'timestamp' => $record['datetime']->toIso8601String(),
            'level' => $record['level_name'],
            'message' => $record['message'],
            'context' => $record['context'],
            'extra' => $record['extra'],
        ];

        return json_encode($formatted) . PHP_EOL;
    }

    public function formatBatch(array $records) {
        return implode('', array_map([$this, 'format'], $records));
    }
}
```

## Audit Logging

Log sensitive operations:

```php
// app/Services/AuditLogger.php
namespace App\Services;

use Illuminate\Support\Facades\Log;

class AuditLogger {
    public static function log($action, $model, $changes = []) {
        Log::channel('audit')->info('Audit log', [
            'action' => $action,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'model' => get_class($model),
            'model_id' => $model->id,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

Usage:
```php
// In controller
$message = ChatMessage::create($validated);
AuditLogger::log('message_created', $message, [
    'conversation_id' => $message->conversation_id,
    'message_length' => strlen($message->message),
]);
```

## Query Logging

Log database queries:

```php
// In AppServiceProvider
public function boot() {
    DB::listen(function ($query) {
        Log::channel('queries')->debug('Database query', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'duration_ms' => $query->time,
            'caller' => $this->getCaller(),
        ]);
    });
}

private function getCaller() {
    $backtrace = debug_backtrace();
    foreach ($backtrace as $trace) {
        if (strpos($trace['file'], 'vendor') === false) {
            return "{$trace['file']}:{$trace['line']}";
        }
    }
    return 'unknown';
}
```

## Log Analysis

### Search Logs
```bash
# Find errors for user
grep '"user_id": 123' storage/logs/requests.log

# Find slow requests
grep 'duration_ms.*[5-9][0-9][0-9]' storage/logs/requests.log

# Count requests by status
grep -o '"status": [0-9]*' storage/logs/requests.log | sort | uniq -c

# Real-time monitoring
tail -f storage/logs/requests.log | jq '.duration_ms'
```

### Using jq
```bash
# Parse JSON logs
tail -f storage/logs/requests.log | jq '.method, .path, .status'

# Filter by status
cat storage/logs/requests.log | jq 'select(.status >= 400)'

# Average response time
cat storage/logs/requests.log | jq '.duration_ms' | awk '{sum+=$1; count++} END {print sum/count}'
```

## Centralized Logging

### ELK Stack Integration
```php
// Store logs in Elasticsearch
'elk' => [
    'driver' => 'monolog',
    'handler' => \Monolog\Handler\ElasticsearchHandler::class,
    'handler_with' => [
        'client' => [
            'hosts' => ['localhost:9200'],
        ],
        'index' => 'lawangsewu-logs',
    ],
],
```

### Splunk Integration
```php
'splunk' => [
    'driver' => 'monolog',
    'handler' => \Monolog\Handler\SyslogUdpHandler::class,
    'handler_with' => [
        'host' => 'splunk-collector.example.com',
        'port' => 514,
    ],
],
```

## Testing

```php
class RequestLoggingTest extends TestCase {
    public function test_requests_are_logged() {
        Log::spy();

        $this->post('/api/messages', ['message' => 'Test']);

        Log::shouldHaveReceived('info')
            ->withArgs(fn($msg) => $msg === 'HTTP request')
            ->once();
    }

    public function test_sensitive_data_is_masked() {
        Log::spy();

        $this->post('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);

        Log::shouldHaveReceived('info')
            ->withArgs(
                fn($msg, $context) =>
                    $context['body']['password'] === '***REDACTED***'
            )
            ->once();
    }
}
```

## Best Practices

- [ ] Log all requests/responses
- [ ] Mask sensitive data
- [ ] Skip health endpoints
- [ ] Include request ID for tracing
- [ ] Include user ID for audit
- [ ] Log timing metrics
- [ ] Log errors separately
- [ ] Use structured logging (JSON)
- [ ] Archive old logs
- [ ] Monitor log file size
- [ ] Use appropriate log levels
- [ ] Include context in logs
- [ ] Test logging in tests
- [ ] Set up log rotation
- [ ] Centralize logs for analysis

## Files Reference

- `config/logging.php` - Logging configuration
- `app/Http/Middleware/LogRequestResponse.php` - Request/response logging
- `app/Services/AuditLogger.php` - Audit logging
- `app/Logging/JsonFormatter.php` - JSON formatting
- `storage/logs/` - Log files
