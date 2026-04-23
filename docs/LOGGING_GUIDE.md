# Structured Logging Guide

## Overview
Comprehensive structured logging system for consistent, queryable log entries across the application.

## Logging Configuration

### Log Channels

Configured in `config/logging.php`:

```php
'channels' => [
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'days' => 14,
    ],
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
    ],
    'wa_caraka' => [
        'driver' => 'single',
        'path' => storage_path('logs/wa_caraka.log'),
        'level' => 'debug',
    ],
    'sipp' => [
        'driver' => 'single',
        'path' => storage_path('logs/sipp.log'),
        'level' => 'info',
    ],
    'performance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/performance.log'),
        'days' => 7,
    ],
]
```

## Logging Best Practices

### 1. Use Structured Context

Instead of string concatenation:
```php
// ❌ Bad
Log::info('User ' . $user->id . ' created at ' . now());

// ✅ Good
Log::info('User created', [
    'user_id' => $user->id,
    'email' => $user->email,
    'timestamp' => now()->toIso8601String(),
]);
```

### 2. Consistent Log Format

All logs should include:
```php
Log::info('Action performed', [
    'action' => 'message_sent',
    'user_id' => auth()->id(),
    'message_id' => $message->id,
    'conversation_id' => $message->conversation_id,
    'recipient_count' => count($recipients),
    'duration_ms' => $stopwatch->stop(),
    'status' => 'success',
    'timestamp' => now()->toIso8601String(),
]);
```

### 3. Log Levels

Use appropriate levels:

```php
// DEBUG - Detailed development info
Log::debug('Variable value', ['variable' => $value]);

// INFO - General informational
Log::info('User logged in', ['user_id' => $user->id]);

// NOTICE - Normal but significant
Log::notice('Configuration changed', ['setting' => 'timeout']);

// WARNING - Potentially harmful situation
Log::warning('Slow query detected', ['query' => $query, 'time_ms' => 1200]);

// ERROR - Error condition but still running
Log::error('Database query failed', ['error' => $e->getMessage()]);

// CRITICAL - Critical condition
Log::critical('Disk space critical', ['free_bytes' => 100000000]);

// ALERT - Action must be taken
Log::alert('Security breach detected', ['ip' => $ip]);

// EMERGENCY - System unusable
Log::emergency('Database offline', ['error' => $e->getMessage()]);
```

### 4. Exception Logging

Log exceptions with full context:

```php
try {
    $data = $service->fetchData();
} catch (WaRuntimeException $e) {
    Log::warning('WA runtime error', [
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'detail' => $e->getDetail(),
        'is_retryable' => $e->isRetryable(),
        'exception_class' => get_class($e),
        'stack_trace' => $e->getTraceAsString(),
    ]);
} catch (Exception $e) {
    Log::error('Unexpected error', [
        'error' => $e->getMessage(),
        'exception' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
}
```

## Service Logging Examples

### WA Caraka Service
```php
class WaCarakaService {
    public function sendMessage($phone, $message) {
        Log::info('Sending WA message', [
            'phone' => $this->maskPhone($phone),
            'message_length' => strlen($message),
            'timestamp' => now()->toIso8601String(),
        ]);

        try {
            $response = $this->post('/send', [
                'to' => $phone,
                'message' => $message,
            ]);
            
            if ($response['ok']) {
                Log::info('WA message sent', [
                    'phone' => $this->maskPhone($phone),
                    'message_id' => $response['message_id'],
                    'duration_ms' => $response['duration'],
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to send WA message', [
                'phone' => $this->maskPhone($phone),
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);
            throw $e;
        }
    }
}
```

### Database Operations
```php
class UserRepository {
    public function create(array $data) {
        Log::debug('Creating user', ['email' => $data['email']]);

        try {
            $user = User::create($data);
            
            Log::info('User created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ]);
            
            return $user;
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') { // Unique constraint
                Log::notice('Duplicate user email', [
                    'email' => $data['email'],
                ]);
            } else {
                Log::error('Failed to create user', [
                    'email' => $data['email'],
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                ]);
            }
            throw $e;
        }
    }
}
```

### API Requests
```php
class ChatController {
    public function storeMessage(StoreChatMessageRequest $request) {
        Log::debug('Processing chat message', [
            'conversation_id' => $request->conversation_id,
            'message_length' => strlen($request->message),
            'user_id' => auth()->id(),
        ]);

        try {
            $message = ChatMessage::create($request->validated());
            
            Log::info('Chat message stored', [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'user_id' => $message->user_id,
                'processed_time_ms' => round((microtime(true) - $start) * 1000),
            ]);
            
            broadcast(new ChatMessageSent($message));
            
            return response()->json(['success' => true, 'message_id' => $message->id]);
        } catch (Exception $e) {
            Log::error('Failed to store chat message', [
                'conversation_id' => $request->conversation_id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

## Log Analysis

### Viewing Logs
```bash
# View live logs
tail -f storage/logs/laravel.log

# Watch specific channel
tail -f storage/logs/wa_caraka.log

# Search logs
grep "error" storage/logs/laravel.log
grep "user_id: 123" storage/logs/laravel.log

# Count occurrences
grep -c "message sent" storage/logs/laravel.log

# Show context around match
grep -A 5 -B 5 "error" storage/logs/laravel.log
```

### Using Laravel Pail (Real-time)
```bash
# View all logs in real-time
php artisan pail

# Filter by level
php artisan pail --level=error

# Filter by type
php artisan pail --message="user"

# Follow specific channel
php artisan pail --channel=wa_caraka
```

### Log File Size Management
```php
// In config/logging.php
'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laravel.log'),
    'days' => 14,  // Keep 14 days
],
```

Archive old logs:
```bash
# Compress logs older than 30 days
find storage/logs -name "*.log" -mtime +30 -exec gzip {} \;

# Delete logs older than 60 days
find storage/logs -name "*.log" -mtime +60 -delete
```

## Sensitive Data

### Masking Sensitive Information
```php
private function maskPhone($phone) {
    return substr($phone, 0, 4) . '****' . substr($phone, -4);
}

private function maskEmail($email) {
    $parts = explode('@', $email);
    return substr($parts[0], 0, 2) . '*****@' . $parts[1];
}

Log::info('User action', [
    'user_email' => $this->maskEmail($user->email),
    'phone' => $this->maskPhone($user->phone),
]);
```

### What NOT to Log
- Passwords
- API keys and tokens
- Full credit card numbers
- Social security numbers
- Personal identification numbers
- Full phone numbers (mask them)
- Full email addresses (optional: mask)
- Full IP addresses in some contexts
- Browser cookies
- Session tokens

## Monitoring & Alerting

### Example: Alert on Errors
```php
// In exception handler
if ($e instanceof WaRuntimeException) {
    Log::error('WA Runtime Error', [
        'error' => $e->getMessage(),
        'status' => $e->getStatusCode(),
    ]);
    
    // Send alert if critical
    if ($e->getStatusCode() >= 500) {
        Notification::route('slack', config('services.slack.alert_webhook'))
            ->notify(new CriticalErrorNotification($e));
    }
}
```

## Performance Logging

```php
class PerformanceLogger {
    public static function logSlowQuery($query, $time) {
        if ($time > 1000) { // > 1 second
            Log::channel('performance')->warning('Slow query', [
                'query' => $query,
                'time_ms' => $time,
                'threshold_ms' => 1000,
            ]);
        }
    }

    public static function logSlowRequest($path, $method, $time) {
        if ($time > 500) { // > 500ms
            Log::channel('performance')->warning('Slow request', [
                'path' => $path,
                'method' => $method,
                'time_ms' => $time,
                'threshold_ms' => 500,
            ]);
        }
    }
}
```

Use in middleware:
```php
class LogSlowRequests {
    public function handle($request, Closure $next) {
        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000;

        PerformanceLogger::logSlowRequest(
            $request->path(),
            $request->method(),
            $duration
        );

        return $response;
    }
}
```

## Centralized Logging

### ELK Stack (Elasticsearch, Logstash, Kibana)
```php
// Log to ELK
'elk' => [
    'driver' => 'monolog',
    'handler' => \Monolog\Handler\ElasticsearchHandler::class,
    'handler_with' => [
        'client' => [
            'hosts' => [env('ELK_HOST', 'localhost:9200')],
        ],
    ],
],
```

### Sentry (Error Tracking)
```php
// In config/services.php
'sentry' => [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    'traces_sample_rate' => 0.1,
    'environment' => config('app.env'),
],
```

## Best Practices Checklist

- [ ] Use structured context arrays instead of string concatenation
- [ ] Include relevant IDs (user_id, message_id, conversation_id, etc.)
- [ ] Log before and after significant operations
- [ ] Include timing/performance metrics
- [ ] Mask sensitive data
- [ ] Use appropriate log levels
- [ ] Include error codes and error messages
- [ ] Log in exceptions handlers
- [ ] Include timestamps for async operations
- [ ] Log important state changes
- [ ] Make logs searchable and queryable
- [ ] Regular cleanup of old log files
- [ ] Monitor log file sizes
- [ ] Set up alerts for errors
- [ ] Review logs regularly

## Files Reference

- `config/logging.php` - Logging configuration
- `app/Services/HealthCheckService.php` - Health monitoring
- `routes/api.php` - Health check endpoints
- `storage/logs/` - Log storage directory
