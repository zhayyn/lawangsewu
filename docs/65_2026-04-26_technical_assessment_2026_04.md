# Technical Assessment: System Implementation Status
**Date:** April 21, 2026  
**Status:** Comprehensive audit of 5 critical areas  
**Overall Assessment:** Mature system with selective optimization areas

---

## 1. DATABASE QUERIES & RELATIONSHIPS

### Current State: GOOD with Targeted Optimizations

#### ✅ Relationship Definitions
- **Models Reviewed:** User, WaCarakaConversation, WaCarakaMessage, ChatMessage, QueueTicket, SippCache, WaCarakaHandover, WaCarakaTicket
- **Relationship Types Used:**
  - `BelongsTo`: User relationships (owner, assignee, requestor, recipient)
  - `HasMany`: Messages, Handovers (WaCarakaConversation → WaCarakaMessage, WaCarakaHandover)
  - `HasOne`: PendingHandover (WaCarakaConversation → single pending handover)
- **Status:** Relationships are well-defined and appropriate

#### ⚠️ N+1 Query Issues - IDENTIFIED & PARTIALLY RESOLVED

**Known N+1 Patterns Found:**
1. **WaCarakaController::getInboxV2()** - Fixed via `joinSub()` optimization
   - Original: Double MAX(id) subqueries with WHERE IN (200-300ms latency)
   - Fixed: Efficient JOIN with indexed conversation_id
   - **Improvement: 200-300ms per request**

2. **Contact Metadata Fetching** - Fixed via async caching
   - Original: Blocking HTTP call to wa-runtime (500ms-2s latency per inbox load)
   - Fixed: Async job with Redis caching (10-minute TTL)
   - **Improvement: 500ms-2s per request**

3. **WaCarakaConversationService** - Selective eager loading observed
   - Uses `.with('assignee:id,name,alias')` (line 29)
   - Uses `.with([...])` for eager loading (line 54)
   - **Status:** Partial - not all services use eager loading consistently

4. **PtspQueueController::index()** - POTENTIAL N+1 ISSUE
   - Calls `PtspQueueTicket::today()` and `.get()` then maps data
   - Does NOT use `with()` for service/counter relationships
   - **Risk Level:** Medium (depends on volume - currently OK but fragile)

#### ❌ Missing Eager Loading Patterns

| Service/Controller | Relationship | Current | Recommended |
|---|---|---|---|
| PtspQueueController | ticket→service, ticket→counter | None | `.with('service', 'counter')` |
| SidangQueueController | ticket→service, ticket→counter | None | `.with('service', 'counter')` |
| WidgetCompatController | PHP script execution | N/A | Cache results |
| Api\PortalApiController | ChatMessage→user | `.load('user')` | Use `.with()` in query |

#### Database Index Status
- **Optimized Indexes:**
  - `wa_caraka_messages(conversation_id)` ✅
  - `wa_caraka_messages(id)` ✅
  - Session implicit composite indexing ✅

- **Recommended Additions:**
  ```sql
  CREATE INDEX idx_ptsp_queue_tickets_today 
    ON ptsp_queue_tickets(queue_date, status);
  
  CREATE INDEX idx_wa_caraka_conversations_claimed 
    ON wa_caraka_conversations(claimed_by, status);
  
  CREATE INDEX idx_chat_messages_user_created 
    ON messages(user_id, created_at DESC);
  ```

#### SIPP Query Patterns
- **Current:** SippHubController uses manual cache validation (`SippCache::query()->active()->first()`)
- **Observation:** Caching logic is manual, not using Laravel's built-in cache methods
- **Risk:** If cache fails, full widget execution on every request
- **Recommendation:** Use `Cache::remember()` for atomic cache-or-execute

---

## 2. QUEUE SYSTEM & BACKGROUND JOBS

### Current State: MINIMAL - Only 2 Jobs Implemented

#### Configuration
```
QUEUE_CONNECTION=database (from .env.example)
```

#### Jobs Implemented
| Job | Purpose | Queue | Status |
|---|---|---|---|
| `SendWaCarakaOutboundMessage` | Send queued WA messages | `wa-caraka` | ✅ Working |
| `FetchWaRuntimeContactMetadata` | Async contact metadata fetch | `default` | ✅ Working |

#### Job Implementation Quality

**SendWaCarakaOutboundMessage:**
```php
- Implements: ShouldQueue
- Dispatched from: WaCarakaService::queueText()
- Conditional dispatch: shouldDispatchOutboundAsync() check
- Retry: Not configured (defaults to 0)
- Timeout: Not configured
```

**FetchWaRuntimeContactMetadata:**
```php
- Implements: ShouldQueue
- Dispatched from: WaCarakaController::getInboxV2()
- Cache integration: Uses Cache::put() with 600s TTL
- Error handling: Catches and logs failures silently
- Batch support: Can process multiple phone numbers
```

#### Job Dispatching Patterns in Controllers

**Event-Based Dispatch:**
- `QueueTicketUpdated::dispatch()` - 4 locations (PtspQueueController, SidangQueueController)
- `ChatMessageSent::dispatch()` - 2 locations (Api\PortalApiController, ChatController)
- `WaCarakaConversationUpdated::dispatch()` - Event class exists

**Current Dispatch Usage:**
```php
// Queue ticket events - Real-time broadcasts
QueueTicketUpdated::dispatch('ptsp', 'created', [...], summary);

// Chat message - Event broadcast
ChatMessageSent::dispatch($message);

// WA Caraka - Message sync
FetchWaRuntimeContactMetadata::dispatch($phoneNumbers);
SendWaCarakaOutboundMessage::dispatch($messageId, $sender);
```

#### ⚠️ Missing Queue Infrastructure

| Feature | Status | Impact |
|---|---|---|
| Job Retry Logic | ❌ None | Failed jobs not retried (data loss risk) |
| Job Timeout | ❌ None | Stuck jobs can block forever |
| Job Monitoring | ❌ None | No visibility into job success/failure |
| Dead Letter Queue | ❌ None | Failed jobs disappear silently |
| Job Batching | ❌ None | No batch processing support |
| Prioritized Queues | ❌ None | All jobs same priority (could prioritize WA messages) |
| Queue Middleware | ❌ None | No before/after hooks |

#### Database-Based Queue Issues

Current config uses `database` driver:
```php
'driver' => 'database',
'retry_after' => 90 seconds,
'after_commit' => false
```

**Problems:**
1. Database locking on queue table during high volume
2. No TTL-based cleanup (jobs table could grow unbounded)
3. Slower than Redis/Beanstalk for high throughput
4. Single point of failure (if DB down, queue down)

**Recommended Migration Path:**
- Short-term: Add `after_commit` => true (transactional safety)
- Medium-term: Add retry logic to critical jobs
- Long-term: Migrate to Redis queue (20x faster, better for real-time)

#### Queue Worker Status
- **Not found in documentation** - Unclear if queue workers are actually running
- **No supervisor configuration** mentioned (need Horizon or manual cron)
- **Potential risk:** Jobs may be queued but not processed

---

## 3. RATE LIMITING SETUP

### Current State: PARTIAL - Auth + Route Level

#### Route-Level Rate Limiting

**Configuration:** Using Laravel's default throttle middleware

**Protected Endpoints:**
```php
// API endpoints - 60 requests per minute
Route::middleware(['auth', 'throttle:60,1'])->prefix('lawangsewu')->group(...)
  /lawangsewu/dashboard
  /lawangsewu/cameras
  /lawangsewu/chat

// Operator endpoints - 30 requests per minute
Route::middleware(['auth', 'throttle:30,1'])->prefix('lawangsewu')->group(...)
  /lawangsewu/chat/messages (POST only)

// Public widget API - 60 requests per minute
Route::middleware(['throttle:60,1'])->group(...)
  /api/pengumuman-rss
  /api/statistik-data
  /api/jadwal-persidangan
  /api/server10
  /api/wa-v2
```

#### Authentication Rate Limiting

**Location:** `app/Http/Requests/Auth/LoginRequest.php`

```php
- Max attempts: 5 failed attempts
- Window: Checked per email+IP combination
- Lockout duration: Until RateLimiter::availableIn() returns 0
- Uses: RateLimiter::tooManyAttempts() with throttleKey()
```

**Throttle Key Formula:**
```
Str::transliterate(Str::lower(email)) . '|' . ip()
```

#### ⚠️ Missing Rate Limiting

| Area | Status | Risk | Recommendation |
|---|---|---|---|
| **WA Webhook** | ❌ None | HIGH | Add IP whitelist + token + rate limit by phone number |
| **Broadcast API** | ⚠️ Per-route only | MEDIUM | Add per-user limit (prevent spam broadcasts) |
| **Admin Actions** | ❌ None | MEDIUM | Rate limit queue refresh, cache clear operations |
| **SIPP Sync** | ❌ None | MEDIUM | Prevent sync storms (limit sync attempts) |
| **File Uploads** | ❌ None | HIGH | Limit upload frequency per user |
| **Socket Events** | ❌ None | MEDIUM | Limit broadcasting frequency (Reverb) |

#### WA Webhook Security

**Current verification (from WaCarakaWebhookController):**
```php
private function verifyWebhookToken(Request $request)
{
    $expectedToken = config('wa_caraka.token', '');
    $receivedToken = $request->header('X-WA-V2-Token', '');

    if ($expectedToken !== '' && !hash_equals($expectedToken, $receivedToken)) {
        Log::warning('[WaCaraka Webhook] Token mismatch from ' . $request->ip());
        return response()->json(['ok' => false, 'error' => 'Unauthorized.'], 401);
    }
    return null;
}
```

**Issues:**
1. ❌ No IP whitelist for webhook source
2. ❌ No rate limiting on webhook endpoint
3. ✅ Uses constant-time comparison (`hash_equals()`)
4. ⚠️ Token stored in config (should use secrets manager)
5. ✅ Logs failed attempts

#### Rate Limit Headers
- **Current:** Laravel default (X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset)
- **Status:** Not explicitly configured
- **Recommendation:** Add custom headers for frontend visibility

---

## 4. LOGGING & MONITORING SETUP

### Current State: FUNCTIONAL - File-Based with Slow Request Tracking

#### Logging Configuration

**File:** `config/logging.php`

```php
'default' => env('LOG_CHANNEL', 'stack')
'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null')
```

**Available Channels:**
| Channel | Driver | Path | Purpose |
|---|---|---|---|
| stack | Stack | Multiple outputs | Default multi-channel |
| single | Single | storage/logs/laravel.log | Primary application log |
| daily | Daily | storage/logs/laravel.log | Rotating daily logs |
| slow_request | Daily | storage/logs/slow-request.log | Performance tracking |
| slack | Slack | ENV webhook | Error alerts to Slack |
| papertrail | Syslog | ENV host:port | Remote logging service |
| stderr | Stream | php://stderr | Console/container logs |
| syslog | Syslog | System syslog | System log integration |
| errorlog | Error Log | System errorlog | PHP error log |

#### Slow Request Middleware

**File:** `app/Http/Middleware/LogSlowRequests.php`

**Configuration:**
```
LOG_SLOW_THRESHOLD_MS=500 (default)
LOG_SLOW_CHANNEL=slow_request
```

**Implementation:**
- Tracks elapsed time using microtime()
- Logs requests exceeding threshold
- Records: elapsed_ms, route, user_id, IP, status
- **Status:** ✅ Working

**Sample Log Entry:**
```json
{
  "message": "[SlowRequest] GET /api/lawangsewu/dashboard",
  "elapsed_ms": 523,
  "threshold": 500,
  "user_id": 42,
  "route": "lawangsewu.dashboard",
  "ip": "192.168.1.100",
  "status": 200
}
```

#### Existing Logging in Controllers

**WaCarakaWebhookController:**
```php
Log::warning('[WaCaraka Webhook] Missing "from" field', [...])
Log::error('[WaCaraka Webhook] handleInbound failed', [...])
Log::warning('[WaCaraka Webhook] Token mismatch from ' . ip)
```

**WaCarakaService:**
```php
Log::error('[WaCaraka] GET/POST failed', [...])
Log::debug('[WaCaraka] Contact metadata cached', [...])
Log::error('[WaCaraka] Failed to fetch contact metadata', [...])
Log::error('[WaCaraka] Failed to dispatch message received event', [...])
```

**WaCarakaSyncRun:**
```php
Log::warning('Widget fetch error', [...])
```

#### ⚠️ Missing Monitoring & Observability

| Feature | Status | Impact |
|---|---|---|
| **Request Tracing** | ❌ None | Can't correlate logs across services |
| **Structured Logging** | ⚠️ Partial | Some logs use structured arrays, inconsistent |
| **Error Tracking** | ❌ No Sentry | Production errors not centralized |
| **APM (Application Performance Monitoring)** | ❌ None | No visibility into response times by endpoint |
| **Queue Monitoring** | ❌ None | Can't track job execution, failures, delays |
| **Health Check Metrics** | ⚠️ Basic | `/health`, `/ready`, `/live` exist but minimal data |
| **Database Query Logging** | ❌ None | Can't audit slow queries or N+1 patterns |
| **Cache Hit Rate** | ❌ None | No metrics on cache effectiveness |
| **WebSocket Monitoring** | ❌ None | Reverb usage not tracked |
| **Feature Flag Observability** | ❌ None | Can't track feature flag usage |

#### Health Check Endpoints

**File:** `app/Http/Controllers/HealthCheckController.php` / `HealthController.php`

```
GET /health  - Basic health check
GET /ready   - Readiness check
GET /live    - Liveness check
```

**Status:** Minimal - likely just returns 200 OK

#### Logging Channel Configuration

**Environment Options Available:**
```
LOG_CHANNEL=stack
LOG_STACK=single (configurable: single, daily, slack, papertrail, syslog, stderr)
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug (configurable: debug, info, notice, warning, error, critical, alert, emergency)
LOG_SLOW_CHANNEL=slow_request
LOG_SLOW_LEVEL=warning
LOG_SLOW_THRESHOLD_MS=500
```

**Slack Integration (Optional):**
```
LOG_SLACK_WEBHOOK_URL=...
LOG_SLACK_USERNAME=Laravel Log
LOG_SLACK_EMOJI=:boom:
LOG_LEVEL=critical
```

**Papertrail Integration (Optional):**
```
PAPERTRAIL_URL=...
PAPERTRAIL_PORT=...
```

#### Database Interaction Logging

**Current:** No query logging middleware found
**Recommendation:** Enable query logging in local/debug environments via:
```php
DB::listen(function ($query) {
    Log::debug('Query: ' . $query->sql, $query->bindings);
});
```

---

## 5. SECURITY MEASURES

### Current State: GOOD - Core Security Implemented

#### Authentication & Authorization

**Guard Configuration:**
```php
'guard' => 'web'
'driver' => 'session'
'provider' => 'users' (Eloquent)
```

**User Model Security:**
- ✅ Password hashing: Uses bcrypt (BCRYPT_ROUNDS=12)
- ✅ Password casting: Protected from serialization
- ✅ Hidden attributes: password, remember_token excluded from arrays

**Middleware Chain:**
```php
auth              // Verify user logged in
verified          // Check email verified (if implemented)
active            // Ensure user account is active (via EnsureActiveUser)
role:{roles}      // Check role permission (via RoleMiddleware)
throttle:{limit}  // Rate limiting
```

#### Role-Based Access Control (RBAC)

**Roles Implemented:**
- `viewer` - Read-only access
- `operator` - Queue/messaging operations
- `useradmin` - User management
- `admin` - Full admin access
- `superadmin` - Ultimate access (checked via is_superadmin flag)

**Implementation:**
- Middleware: `RoleMiddleware` in `app/Http/Middleware/`
- Check method: `User::hasAnyRole(['role1', 'role2'])`
- Super admin override: Bypasses all role checks

**EnsureActiveUser Middleware:**
```php
- Checks: $user->is_active flag
- Action: Logs out inactive users with message
- Returns: 403 if JSON request, redirect if web
```

#### Authentication Configuration

**From `config/auth.php`:**
```php
'super_admin_email' => env('SUPERADMIN_EMAIL', 'dbprakom@gmail.com')
'allow_public_registration' => false
'password_reset_expire' => 60 minutes
'password_reset_throttle' => 60 seconds
'password_timeout' => 10800 seconds (3 hours)
```

#### Encryption

**Application Key:**
```
APP_KEY=base64:... (from .env)
cipher=AES-256-CBC (from config/app.php)
```

**Session Encryption:**
```
SESSION_ENCRYPT=false (from .env.example)
```

**Risk:** Session data is NOT encrypted by default. Options:
- Set `SESSION_ENCRYPT=true` for encrypted sessions
- Already using database sessions (safer than file/cookie)

#### CORS Configuration

**Status:** ❌ NOT EXPLICITLY CONFIGURED

**Current Behavior:**
- No `config/cors.php` found
- No explicit CORS middleware applied
- Laravel defaults: Allow all origins for `api/*` routes

**Risk:** If adding cross-origin APIs, CORS is not protected

**Recommended Addition:**
```php
// config/cors.php or middleware
'allowed_origins' => [env('APP_URL'), 'https://trusted-domain.com'],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
'allowed_headers' => ['Content-Type', 'Authorization'],
'max_age' => 3600,
```

#### Database Security

**File:** `config/database.php`

**Connection Options:**
- SQLite (development)
- MySQL (production)
- MariaDB (alternative)

**MySQL/MariaDB Configuration:**
```php
'strict' => true  // ✅ Strict mode enabled
'charset' => 'utf8mb4'  // ✅ UTF-8 with emoji support
'collation' => 'utf8mb4_unicode_ci'  // ✅ Proper collation
'ssl_ca' => env('MYSQL_ATTR_SSL_CA')  // ✅ SSL support configured
```

#### Webhook Security

**WA Caraka Webhook Verification:**
```php
// From WaCarakaWebhookController::verifyWebhookToken()
- Header check: X-WA-V2-Token
- Comparison: hash_equals() (constant-time, prevents timing attacks)
- Token source: config('wa_caraka.token')
- Logging: Logs failed attempts with IP
```

**Endpoints:**
```
POST /api/wa-caraka/webhook/inbound (protected by token)
POST /api/wa-caraka/webhook/history-sync (protected by token)
```

**Issues:**
- ⚠️ Token stored in config (should use .env secrets)
- ❌ No IP whitelist enforcement
- ❌ No rate limiting on webhook
- ✅ Proper constant-time comparison
- ✅ Failed attempts logged

#### Environment Configuration Security

**From `.env.example`:**

**Sensitive Configs Present:**
```
APP_KEY=              (must be set)
APP_DEBUG=true        (should be false in production)
SIPP_DB_PASSWORD=     (exposed in example)
LW_WA_V2_TOKEN=       (exposed in example)
REVERB_APP_SECRET=    (exposed in example)
SUPERADMIN_EMAIL=     (specific email in code)
```

**Missing from .env.example:**
- No CORS configuration
- No HTTPS enforcement
- No security headers configuration

#### Database Constraints

**Foreign Key Constraints:**
```php
'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true)
```

**Status:** ✅ Enabled by default (prevents orphaned records)

#### Session Security

**Configuration:**
```php
'lifetime' => 120 minutes  // 2 hour timeout
'encrypt' => false         // ⚠️ Not encrypted
'domain' => null           // Same domain only
'path' => '/'              // Root path
'http_only' => true        // Default Laravel behavior
'secure' => null           // Set to true in production
'same_site' => 'lax'       // Default CSRF protection
```

**Recommendations:**
- Set `SESSION_ENCRYPT=true`
- Set `SESSION_SECURE=true` (HTTPS only)
- Reduce lifetime to 60 minutes if handling sensitive data

#### CSRF Protection

**Status:** ✅ Enabled by default (Laravel middleware)

**Mechanism:**
- CSRF token in forms (VerifyCsrfToken middleware)
- Double-submit cookie pattern
- Same-site cookie protection

#### Input Validation

**Examples Found:**
```php
// LoginRequest - proper validation rules
'email' => ['required', 'string', 'email']
'password' => ['required', 'string']

// PortalApiController - file upload validation
'attachment' => File::types(['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'mov'])->max(2048)

// PtspQueueController - basic validation
'service_desk' => ['required', 'string', 'max:30']
'visitor_name' => ['nullable', 'string', 'max:120']
'purpose' => ['nullable', 'string', 'max:255']
```

**Status:** ✅ FormRequest validation in place, but not comprehensive across all endpoints

#### SQL Injection Prevention

**Status:** ✅ Parametrized queries throughout
- All queries use Eloquent ORM or query builder
- Parameter binding automatic (no raw SQL concatenation)
- Exception: `whereRaw()` used in some query optimization (analyzed, safe)

#### XSS Prevention

**Status:** ✅ Blade templating with auto-escaping
**Caveat:** Need to verify Vue.js rendering (Inertia.js)

---

## SUMMARY TABLE: Implementation Status

| Area | Feature | Status | Priority |
|---|---|---|---|
| **Database** | Eager loading | ⚠️ Partial | HIGH |
| **Database** | N+1 optimization | ✅ Addressed | MEDIUM |
| **Database** | Index strategy | ✅ Good | LOW |
| **Queue** | Job implementation | ✅ Basic | HIGH |
| **Queue** | Retry logic | ❌ Missing | HIGH |
| **Queue** | Monitoring | ❌ Missing | MEDIUM |
| **Queue** | Queue workers | ❓ Unknown | HIGH |
| **Rate Limit** | Route level | ✅ Implemented | LOW |
| **Rate Limit** | Auth level | ✅ Implemented | LOW |
| **Rate Limit** | Webhook | ⚠️ Token only | HIGH |
| **Rate Limit** | Broadcast | ⚠️ Route only | MEDIUM |
| **Logging** | File logging | ✅ Configured | LOW |
| **Logging** | Slow queries | ✅ Middleware | LOW |
| **Logging** | Error tracking | ❌ No Sentry | HIGH |
| **Logging** | APM/Tracing | ❌ Missing | MEDIUM |
| **Logging** | Structured logging | ⚠️ Partial | MEDIUM |
| **Security** | Authentication | ✅ Solid | LOW |
| **Security** | Authorization | ✅ RBAC working | LOW |
| **Security** | Encryption (app) | ✅ AES-256-CBC | LOW |
| **Security** | Encryption (session) | ❌ Not enabled | MEDIUM |
| **Security** | CORS | ❌ Not configured | MEDIUM |
| **Security** | Webhook validation | ✅ Token+hash_equals | LOW |
| **Security** | Webhook IP whitelist | ❌ Missing | HIGH |
| **Security** | SQL injection | ✅ Protected | LOW |
| **Security** | XSS prevention | ✅ Auto-escaped | LOW |

---

## RECOMMENDATIONS BY PRIORITY

### 🔴 CRITICAL (Implement First)

1. **Add Job Retry Logic** - Currently no retries; implement exponential backoff
2. **Implement Queue Worker Monitoring** - Verify workers are actually running
3. **Add Error Tracking** - Integrate Sentry or similar for production errors
4. **WA Webhook IP Whitelist** - Restrict webhook source to known IPs
5. **Enable Session Encryption** - Set SESSION_ENCRYPT=true
6. **Eager Load in All Services** - Complete N+1 prevention across controllers

### 🟠 HIGH (Within Sprint)

1. **Add APM Monitoring** - Query timing, endpoint performance visibility
2. **Complete Rate Limiting** - Admin actions, broadcast, file uploads
3. **Implement Dead Letter Queue** - Don't lose failed jobs silently
4. **Structured Logging** - Consistent JSON logging for aggregation
5. **CORS Configuration** - Explicitly define allowed origins
6. **Migrate Queue to Redis** - Database queue is bottleneck

### 🟡 MEDIUM (Next Sprint)

1. **Add Health Check Metrics** - Return actual service status details
2. **Database Query Auditing** - Identify remaining slow queries
3. **Queue Prioritization** - Prioritize critical jobs (WA messages)
4. **Cache Metrics** - Track hit rate and TTL effectiveness
5. **Webhook Signature Verification** - Add HMAC signing (not just token)
6. **Request Tracing** - Add X-Request-ID for log correlation

### 🟢 LOW (Future)

1. **Session Activity Logging** - Track user login/logout patterns
2. **Feature Flag Metrics** - Monitor which flags are used
3. **WebSocket Monitoring** - Track Reverb connections/messages
4. **Secrets Rotation** - Automated token/password rotation
5. **Rate Limit Analytics** - Visual dashboard of rate limiting hits

---

## KNOWN TECHNICAL DEBT

1. **Manual Cache Management** - Use `Cache::remember()` instead of manual checks
2. **WaCarakaService Complexity** - 2000+ lines, needs refactoring
3. **Inconsistent Error Handling** - Some endpoints throw, some return JSON
4. **Widget PHP Scripts** - Legacy integration, consider API normalization
5. **Database Migrations** - Need foreign key constraint review
6. **Event Broadcasting** - Events dispatched but unclear if listeners configured
7. **Config Duplication** - Some values in both config files and .env

