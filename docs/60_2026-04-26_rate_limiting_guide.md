# Rate Limiting Guide

## Overview

Comprehensive rate limiting to prevent abuse and protect application resources.

## Configuration

Create `config/rate_limit.php`:

```php
return [
    'enabled' => env('RATE_LIMIT_ENABLED', true),
    
    'limits' => [
        'default' => '60,1',  // 60 requests per minute
        'api' => '100,1',     // 100 requests per minute for authenticated API
        'auth' => '5,1',      // 5 requests per minute for login attempts
        'wa_webhook' => '1000,1', // High for webhook processing
    ],

    'cache_driver' => env('RATE_LIMIT_CACHE', 'cache'),
    
    'skip_paths' => [
        'health',
        'ready',
        'live',
    ],

    'response' => [
        'status_code' => 429,
        'message' => 'Too many requests. Please try again in :retry_after seconds.',
    ],
];
```

## Middleware Implementation

### Global Rate Limiting
```php
// app/Http/Middleware/RateLimitRequests.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitRequests {
    public function handle(Request $request, Closure $next) {
        if (!config('rate_limit.enabled')) {
            return $next($request);
        }

        // Skip health endpoints
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Get appropriate limit
        $limit = $this->getLimit($request);
        $key = $this->getKey($request);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'error' => 'Too many requests',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after_seconds' => $seconds,
                'timestamp' => now()->toIso8601String(),
            ], 429)
            ->header('Retry-After', $seconds);
        }

        RateLimiter::hit($key, 1);
        
        $response = $next($request);
        
        // Add rate limit headers
        return $response
            ->header('X-RateLimit-Limit', config('rate_limit.limits.default'))
            ->header('X-RateLimit-Remaining', RateLimiter::remaining($key, $limit))
            ->header('X-RateLimit-Reset', RateLimiter::resetAfter($key));
    }

    private function shouldSkip(Request $request) {
        foreach (config('rate_limit.skip_paths') as $path) {
            if ($request->is($path)) {
                return true;
            }
        }
        return false;
    }

    private function getLimit(Request $request) {
        if ($request->is('api/*') && auth()->check()) {
            return config('rate_limit.limits.api', 100);
        }
        
        if ($request->is('*/login', '*/register')) {
            return config('rate_limit.limits.auth', 5);
        }

        return config('rate_limit.limits.default', 60);
    }

    private function getKey(Request $request) {
        $identifier = auth()->check() ? auth()->id() : $request->ip();
        $method = $request->method();
        $path = $request->path();
        
        return "{$method}|{$path}|{$identifier}";
    }
}
```

### User-Specific Rate Limiting
```php
// Routes
Route::middleware(['auth', 'throttle:api_limit'])->group(function () {
    Route::post('/messages', [ChatController::class, 'store']);
});

// config/rate_limit.php
'limits' => [
    'api_limit' => '100,1',  // Per authenticated user
],
```

## Advanced Strategies

### Dynamic Rate Limiting by User Role
```php
class DynamicRateLimiter {
    public static function getLimit($request) {
        $user = auth()->user();
        
        return match ($user?->role) {
            'admin' => 1000,
            'operator' => 500,
            'useradmin' => 200,
            'viewer' => 100,
            default => 60,
        };
    }
}
```

### IP-Based Rate Limiting
```php
$key = $request->ip(); // Use IP instead of user ID

RateLimiter::tooManyAttempts($key, 100, 60); // 100 requests per hour
```

### Sliding Window Algorithm
```php
class SlidingWindowRateLimiter {
    public function isAllowed($key, $limit, $windowMinutes) {
        $now = now();
        $window = $now->copy()->subMinutes($windowMinutes);
        
        $count = Cache::get($key . ':requests', [])
            ->filter(fn($time) => $time > $window)
            ->count();

        if ($count >= $limit) {
            return false;
        }

        Cache::put(
            $key . ':requests',
            [...Cache::get($key . ':requests', []), $now],
            $windowMinutes * 60
        );

        return true;
    }
}
```

## Monitoring & Alerts

```php
class RateLimitMonitor {
    public static function trackLimit($key, $limit) {
        $exceeded = Cache::get($key . ':exceeded', 0);
        
        if ($exceeded > $limit * 0.8) {
            Log::warning('High rate limit usage', [
                'key' => $key,
                'usage_percent' => ($exceeded / $limit) * 100,
                'limit' => $limit,
            ]);

            if ($exceeded >= $limit) {
                Notification::route('slack', config('services.slack.alert_webhook'))
                    ->notify(new RateLimitExceededAlert($key));
            }
        }
    }
}
```

## Testing

```php
class RateLimitTest extends TestCase {
    public function test_rate_limit_blocks_excess_requests() {
        $response = $this->get('/api/messages');
        $this->assertEquals(200, $response->status());

        // Make requests up to limit
        for ($i = 0; $i < 99; $i++) {
            $this->get('/api/messages');
        }

        // Next request should fail
        $response = $this->get('/api/messages');
        $this->assertEquals(429, $response->status());
    }

    public function test_rate_limit_resets_after_window() {
        Config::set('rate_limit.limits.default', '2,1'); // 2 per minute

        $this->get('/api/messages');
        $this->get('/api/messages');
        
        $response = $this->get('/api/messages');
        $this->assertEquals(429, $response->status());

        // Advance time
        $this->travelTo(now()->addMinute());
        
        $response = $this->get('/api/messages');
        $this->assertEquals(200, $response->status());
    }
}
```

## Best Practices

- [ ] Set appropriate limits per endpoint
- [ ] Use user ID for authenticated requests
- [ ] Use IP for unauthenticated requests
- [ ] Skip health/status endpoints
- [ ] Add rate limit headers
- [ ] Provide clear error messages
- [ ] Log rate limit violations
- [ ] Monitor limit usage
- [ ] Adjust limits based on data
- [ ] Consider burst capacity
- [ ] Use sliding window algorithm
- [ ] Test rate limiting thoroughly
- [ ] Document limits in API docs
- [ ] Provide retry-after header
- [ ] Exempt critical operations

## Files Reference

- `config/rate_limit.php` - Rate limit configuration
- `app/Http/Middleware/RateLimitRequests.php` - Rate limit middleware
- Routes with `:throttle` - Route-level limits
