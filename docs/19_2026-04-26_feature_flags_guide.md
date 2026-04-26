# Feature Flags Guide

## Overview
Feature flags enable safe, controlled rollout of features without redeployment.

## Configuration

### Base Configuration
File: `config/features.php`

```php
return [
    'wa_caraka' => env('FEATURE_WA_CARAKA', true),
    'sipp_hub' => env('FEATURE_SIPP_HUB', true),
    'inbox_optimization' => env('FEATURE_INBOX_OPTIMIZATION', true),
    'advanced_search' => env('FEATURE_ADVANCED_SEARCH', true),
];
```

## Usage Examples

### 1. Service Level
```php
class ChatMessageService {
    public function sendMessage($message) {
        if (!config('features.wa_caraka')) {
            throw new FeatureDisabledException('WA Caraka not enabled');
        }
        
        return $this->waCarakaService->send($message);
    }
}
```

### 2. Controller Level
```php
class ChatController {
    public function store(StoreChatMessageRequest $request) {
        if (config('features.wa_caraka')) {
            $this->sendViaWaCaraka($request->message);
        } else {
            $this->sendViaDatabaseOnly($request->message);
        }
    }
}
```

### 3. Blade Template
```blade
@if(config('features.advanced_search'))
    <div class="search-panel">
        <!-- Advanced search UI -->
    </div>
@endif
```

### 4. Route Level
```php
Route::middleware(['feature:wa_caraka'])->group(function () {
    Route::post('/messages/wa', [ChatController::class, 'sendViaWa']);
});
```

## Feature Middleware

Create custom middleware:

```php
// app/Http/Middleware/CheckFeature.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckFeature {
    public function handle(Request $request, Closure $next, string $feature) {
        if (!config("features.{$feature}")) {
            return response()->json([
                'error' => 'Feature not available',
                'feature' => $feature,
            ], 503);
        }

        return $next($request);
    }
}
```

Register in `bootstrap/app.php`:
```php
$app->make(Kernel::class)->pushMiddleware(\App\Http\Middleware\CheckFeature::class);
```

## Progressive Rollout Pattern

### Strategy 1: User Percentage
```php
class FeatureFlagService {
    public function isEnabledForUser($userId, $feature, $percentage = 50) {
        $hash = crc32($userId . $feature) % 100;
        return $hash < $percentage;
    }
}

// Usage
if (app(FeatureFlagService::class)->isEnabledForUser(auth()->id(), 'advanced_search', 10)) {
    // Show to 10% of users
}
```

### Strategy 2: User List
```php
class FeatureFlagService {
    public function isEnabledForUser($userId, $feature) {
        $whitelist = config("features.whitelist.{$feature}", []);
        return in_array($userId, $whitelist);
    }
}

// config/features.php
'whitelist' => [
    'advanced_search' => [1, 2, 5, 10],
    'sipp_hub' => [1, 3, 4, 100],
],
```

### Strategy 3: User Role
```php
if (auth()->user()->hasRole('admin')) {
    // Always show to admins
    $enableFeature = true;
} elseif (config('features.advanced_search')) {
    // Show to others based on flag
    $enableFeature = true;
} else {
    $enableFeature = false;
}
```

## Advanced Implementation

### Feature Manager Class
```php
namespace App\Support;

class FeatureFlagManager {
    private $features;
    private $userWhitelist = [];

    public function __construct() {
        $this->features = config('features', []);
    }

    public function isEnabled($feature, $userId = null) {
        // Check if globally enabled
        if (!($this->features[$feature] ?? false)) {
            return false;
        }

        // Check user whitelist if userId provided
        if ($userId && isset($this->userWhitelist[$feature])) {
            return in_array($userId, $this->userWhitelist[$feature]);
        }

        return true;
    }

    public function withWhitelist($feature, $userIds) {
        $this->userWhitelist[$feature] = $userIds;
        return $this;
    }

    public function getAllEnabled() {
        return array_filter($this->features);
    }

    public function getStatus() {
        return [
            'features' => $this->features,
            'enabled_count' => count(array_filter($this->features)),
            'total_count' => count($this->features),
        ];
    }
}
```

### Usage in Service Provider
```php
class AppServiceProvider extends ServiceProvider {
    public function register() {
        $this->app->singleton(FeatureFlagManager::class);
    }

    public function boot() {
        // Load user-specific whitelists
        $manager = app(FeatureFlagManager::class);
        $manager->withWhitelist('advanced_search', [1, 2, 5]);
    }
}
```

## Monitoring & Logging

```php
class FeatureFlagMiddleware {
    public function handle($request, Closure $next) {
        $enabled = [
            'wa_caraka' => config('features.wa_caraka'),
            'sipp_hub' => config('features.sipp_hub'),
            'advanced_search' => config('features.advanced_search'),
        ];

        Log::debug('Feature flags', $enabled);
        
        return $next($request);
    }
}
```

### Track Feature Usage
```php
class FeatureUsageLogger {
    public static function log($feature, $userId = null, $action = 'used') {
        Log::info('Feature usage', [
            'feature' => $feature,
            'user_id' => $userId,
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

// In controller
FeatureUsageLogger::log('advanced_search', auth()->id(), 'search_executed');
```

## Testing with Feature Flags

### Unit Tests
```php
class SearchControllerTest extends TestCase {
    public function test_advanced_search_only_when_enabled() {
        // Feature disabled
        config(['features.advanced_search' => false]);
        $response = $this->get('/api/search/advanced');
        $response->assertStatus(503);

        // Feature enabled
        config(['features.advanced_search' => true]);
        $response = $this->get('/api/search/advanced');
        $response->assertStatus(200);
    }
}
```

### Feature Tests
```php
class FeatureFlagTest extends TestCase {
    public function test_feature_enabled_for_whitelisted_users() {
        $user = User::factory()->create(['id' => 1]);
        $this->actingAs($user);

        config(['features.advanced_search' => true]);
        config(['features.whitelist.advanced_search' => [1]]);

        $this->assertTrue(app(FeatureFlagManager::class)->isEnabled('advanced_search', 1));
    }

    public function test_feature_disabled_for_non_whitelisted_users() {
        $user = User::factory()->create(['id' => 999]);
        
        config(['features.whitelist.advanced_search' => [1, 2]]);

        $this->assertFalse(app(FeatureFlagManager::class)->isEnabled('advanced_search', 999));
    }
}
```

## Deployment Strategy

### Zero-Downtime Feature Rollout
```
1. Deploy code with feature flag disabled
   FEATURE_NEW_FEATURE=false

2. Verify on staging
   - All new code paths are correct
   - No errors in logs
   - Database migrations work

3. Enable for 10% of users
   FEATURE_NEW_FEATURE=true (with 10% rollout)

4. Monitor:
   - Error rates
   - Performance metrics
   - User feedback

5. Increase to 50% if no issues
6. Increase to 100%
7. Remove feature flag code after stable
```

## Environment Configuration

### Development
```env
FEATURE_WA_CARAKA=true
FEATURE_SIPP_HUB=true
FEATURE_ADVANCED_SEARCH=true
FEATURE_INBOX_OPTIMIZATION=true
```

### Staging
```env
FEATURE_WA_CARAKA=true
FEATURE_SIPP_HUB=true
FEATURE_ADVANCED_SEARCH=false
FEATURE_INBOX_OPTIMIZATION=false
```

### Production
```env
FEATURE_WA_CARAKA=true
FEATURE_SIPP_HUB=true
FEATURE_ADVANCED_SEARCH=false
FEATURE_INBOX_OPTIMIZATION=false
```

## Health Check Integration

```php
class HealthCheckService {
    public function featureFlagsStatus() {
        return [
            'wa_caraka' => config('features.wa_caraka'),
            'sipp_hub' => config('features.sipp_hub'),
            'advanced_search' => config('features.advanced_search'),
            'inbox_optimization' => config('features.inbox_optimization'),
        ];
    }
}

// Endpoint
GET /api/health → includes feature flags in response
```

## Best Practices

- [ ] Keep feature flags simple (boolean or percentage)
- [ ] Document why each flag exists
- [ ] Add expiration date for temporary flags
- [ ] Remove flags when feature is stable
- [ ] Monitor feature usage
- [ ] Test with flag enabled and disabled
- [ ] Use consistent naming (FEATURE_*)
- [ ] Log feature flag decisions
- [ ] Version control flag changes
- [ ] Include in CI/CD pipeline
- [ ] Set sensible defaults
- [ ] Document rollback strategy
- [ ] Communicate flag changes to team

## Files Reference

- `config/features.php` - Feature flag configuration
- `app/Support/FeatureFlagManager.php` - Feature flag management
- `routes/api.php` - Include feature flag routes
- Tests should verify both enabled and disabled paths
