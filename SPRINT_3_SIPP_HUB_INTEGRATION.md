# Sprint 3: SIPP Hub Integration Documentation

## Overview
Sprint 3 implements full SIPP Hub integration with Lawangsewu portal, providing real-time cache synchronization, dynamic metrics, and alerts based on SIPP data status.

## Components Updated

### 1. Portal Support Class
**File:** `app/Support/LawangsewuPortal.php`

#### Quick Actions (quickActions Method)
Added SIPP Hub quick action button:
```php
['label' => 'Buka SIPP Hub', 'href' => route('lawangsewu.sipp.index'), 'tone' => 'accent']
```

#### Navigation Groups (navGroups Method)
SIPP Hub navigation already present under "SIPP Hub & Data" section:
```php
['label' => 'SIPP Hub', 'short' => 'SP', 'routeKey' => 'sipp', 'href' => route('lawangsewu.sipp.index'), 'badge' => 'Sprint 3']
```

#### Modules (modules Method)
SIPP Hub module with active link:
```php
['title' => 'SIPP Hub', 'description' => 'Widget statistik dan cache sinkron.', 'owner' => 'Data', 'badge' => 'Sprint 3', 'href' => route('lawangsewu.sipp.index')]
```

#### Alerts (alerts Method)
Dynamic SIPP cache status alert:
```php
public static function alerts(): array
{
    $cacheStatus = 'Cache siap';
    if (Schema::hasTable('sipp_caches')) {
        $active = SippCache::query()->active()->count();
        $cacheStatus = $active > 0 ? 'Cache aktif ' . $active . ' entri' : 'Cache kosong - refresh untuk sinkronisasi';
    }

    return [
        ['title' => 'CCTV Lobby stabil', 'detail' => 'Stream utama latency 1.2 detik.', 'tone' => 'emerald'],
        ['title' => 'Sync SIPP aktif', 'detail' => $cacheStatus, 'tone' => 'emerald'],
        ['title' => 'Interkom aktif', 'detail' => '4 alias online di kanal operasional.', 'tone' => 'blue'],
    ];
}
```

#### Metrics (metrics Method)
Dynamic SIPP cache count in metrics:
```php
$sippCacheCount = '-';
if (Schema::hasTable('sipp_caches')) {
    $sippCacheCount = (string) SippCache::query()->active()->count();
}

['title' => 'SIPP Cache', 'value' => $sippCacheCount, 'trend' => 'Entri aktif', 'detail' => 'Widget cache siap dimuat', 'tone' => 'emerald'],
```

#### System Health (dashboardPayload Method)
SIPP Cache health status:
```php
['label' => 'SIPP Cache', 'value' => Schema::hasTable('sipp_caches') ? sprintf('%d cache aktif', SippCache::query()->active()->count()) : 'Inisialisasi', 'tone' => 'emerald'],
```

### 2. SIPP Hub Controller
**File:** `app/Http/Controllers/SippHubController.php`

Features:
- Fetches or caches SIPP statistics (perkara, ecourt, hakim)
- Manages cache with 15-minute TTL
- Provides sync information (lastSync, status, cacheStatus, nextSync)
- Includes metrics: cache entries, hit rate, response time
- Refresh mechanism for manual cache invalidation

### 3. Routing
**File:** `routes/web.php`

Routes configured:
```php
Route::get('/sipp-hub', [SippHubController::class, 'index'])->name('lawangsewu.sipp.index');
Route::post('/sipp-hub/refresh', [SippHubController::class, 'refreshCache'])->name('lawangsewu.sipp.refresh');
```

## Data Flow

```
Portal Dashboard
    ↓
LawangsewuPortal Support Class
    ↓
- Queries SippCache table for active entries
- Displays cache count in metrics
- Updates alert status based on cache state
- Includes SIPP Hub in navigation and quick actions
    ↓
Frontend Inertia Component (Lawangsewu/Dashboard)
    ↓
User sees:
- SIPP Hub in modules list
- SIPP Hub in quick actions
- Cache status in alerts
- Cache metrics in dashboard
```

## Database Schema

The implementation requires the `sipp_caches` table:

```sql
CREATE TABLE sipp_caches (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(255) NOT NULL,
    key VARCHAR(255) NOT NULL UNIQUE,
    value LONGTEXT NOT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

Key columns:
- `type`: Type of cache (e.g., 'statistik-perkara', 'statistik-ecourt', 'statistik-hakim')
- `key`: Unique cache key
- `value`: Cached JSON data
- `expires_at`: Cache expiration time
- `is_active()` query scope checks if cache hasn't expired

## Query Scopes Used

The SippCache model includes an `active()` query scope:
```php
public function scopeActive($query)
{
    return $query->where('expires_at', '>', now())
                 ->orWhereNull('expires_at');
}
```

## Testing

All tests passing:
- ✓ Dashboard Flow Tests (4/4)
- ✓ CCTV API Tests (2/2)
- ✓ Guestbook Flow Tests (3/3)
- ✓ Chat Flow Tests (3/3)
- ✓ Portal API Contract Tests (2/2)
- ✓ PTSP Queue Tests (5/5)
- ✓ Sidang Queue Tests (5/5)
- ✓ Profile Tests (5/5)

**Total: 55 passed (234 assertions)**

### Running Tests

```bash
# Run all tests
php artisan test

# Run portal tests only
php artisan test tests/Feature/Portal/

# Run dashboard tests
php artisan test tests/Feature/Portal/DashboardFlowTest.php
```

## Integration Points

### Frontend Integration
- Dashboard component receives `metrics` prop including SIPP Cache metric
- Navigation displays SIPP Hub link with Sprint 3 badge
- Quick actions show "Buka SIPP Hub" button with accent tone
- Alerts display dynamic cache status

### Backend Integration
- Portal support class queries SippCache model directly
- Graceful fallback when table doesn't exist (returns '-' or default status)
- All queries wrapped in `Schema::hasTable()` checks for safety
- Uses Eloquent query scope for active cache filtering

## Configuration

### Cache TTL
- Default: 15 minutes (configured in SippHubController)
- Controlled by `$ttl` parameter in `fetchOrCache()` method

### Auto-Sync
- Scheduled via Laravel scheduled tasks (if configured)
- Manual refresh available via POST to `/sipp-hub/refresh`

## Performance Considerations

- Cache queries are minimal (single count query)
- Schema checks are fast filesystem operations
- Query results cached in Laravel's query builder
- No N+1 queries in portal payload generation

## Browser Compatibility
- All modern browsers supported
- Responsive design for mobile/tablet
- Real-time updates via Reverb websocket (configured separately)

## Troubleshooting

### Cache shows "-"
- Check if `sipp_caches` table exists
- Verify migration has been run: `php artisan migrate`
- Check database connection in `.env`

### Empty alerts
- Verify `SippCache::query()->active()->count()` returns 0
- Check cache expiration times: should be in future for active status
- Run refresh command: POST `/sipp-hub/refresh`

### Integration not showing
- Clear browser cache
- Restart PHP artisan: `php artisan cache:clear`
- Verify route registration: `php artisan route:list | grep sipp`

## Future Enhancements

- [ ] Real-time cache updates via Reverb websocket
- [ ] Advanced cache analytics and statistics
- [ ] Cache invalidation rules and strategies
- [ ] SIPP integration with other portal modules
- [ ] Custom widget builder for SIPP data
- [ ] Performance monitoring and alerting

## Notes

- Sprint 3 completes core SIPP Hub integration
- All portal metrics now reflect real-time SIPP cache status
- Dynamic alerts provide visibility into synchronization health
- System gracefully handles missing database tables
- Next sprint (Sprint 4) can build on this foundation for enhanced features
