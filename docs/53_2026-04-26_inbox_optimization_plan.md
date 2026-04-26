# WA Caraka Inbox Query Optimization Plan

**Date:** 2026-04-21
**Status:** IN PROGRESS
**Goal:** Reduce inbox load latency from database queries (not just frontend)

---

## Current Performance Baseline

### Identified Bottlenecks (by impact):

| # | Bottleneck | Location | Latency | Impact |
|---|-----------|----------|---------|--------|
| 1 | **wa-runtime HTTP call** | Lines 286-298 | 500ms-2s | **CRITICAL** |
| 2 | **Double MAX(id) subquery** | Lines 264-294 | 200-500ms | **HIGH** |
| 3 | **Separate marks query** | Lines 247-251 | 50-100ms | MODERATE |
| 4 | **Client-side dedup** | Line 241 | 10-50ms | LOW |

**Total Inbox Load Time:** 1-3 seconds (measured from client)
**DB Query Time (estimated):** 300-700ms (without runtime call)
**Runtime Call:** 500ms-2s (blocking)

---

## Implementation Plan

### Phase 1: Database Query Optimization (this sprint)

#### Step 1.1: Replace MAX(id) with Window Function
- **File:** `app/Http/Controllers/WaCarakaController.php` (getInboxV2 method)
- **Change:** Replace raw SQL subqueries with efficient window function or ROW_NUMBER()
- **Expected Improvement:** 200-300ms reduction
- **Database Compatibility:** MySQL 8.0+ (uses window functions)

**Before:**
```php
->whereRaw('id IN (
    SELECT MAX(id) FROM wa_caraka_messages 
    WHERE conversation_id IN (...)
    GROUP BY conversation_id
)', $conversationIds)
```

**After (Option A - Window Function):**
```php
->joinSub(
    WaCarakaMessage::whereIn('conversation_id', $conversationIds)
        ->selectRaw('conversation_id, id, ROW_NUMBER() OVER (PARTITION BY conversation_id ORDER BY id DESC) as rn')
        ->where('rn', 1),
    'latest',
    function ($join) {
        $join->on('wa_caraka_messages.conversation_id', '=', 'latest.conversation_id')
             ->where('latest.rn', 1);
    }
)
```

**After (Option B - Simpler GROUP BY MAX):**
```php
->joinSub(
    WaCarakaMessage::whereIn('conversation_id', $conversationIds)
        ->selectRaw('conversation_id, MAX(id) as max_id')
        ->groupBy('conversation_id'),
    'latest',
    function ($join) {
        $join->on('wa_caraka_messages.id', '=', 'latest.max_id');
    }
)
```

---

#### Step 1.2: Combine Marks Query with Main Query
- **File:** `app/Http/Controllers/WaCarakaController.php` (getInboxV2 method)
- **Change:** Use LEFT JOIN to include conversation marks in initial query
- **Expected Improvement:** 50-100ms reduction
- **Complexity:** Medium (need to handle null marks in result mapping)

**Implementation:**
```php
->leftJoin(
    'wa_caraka_conversation_marks as marks',
    function($join) use ($user) {
        $join->on('wa_caraka_conversations.id', '=', 'marks.wa_caraka_conversation_id')
             ->where('marks.user_id', $user->id);
    }
)
->select([
    'wa_caraka_conversations.*',
    'marks.label',
    'marks.tone',
    'marks.note',
    'marks.is_pinned'
])
```

---

#### Step 1.3: Fix Client-Side Deduplication
- **File:** `app/Http/Controllers/WaCarakaController.php` (getInboxV2 method)
- **Change:** Remove `.unique('remote_number')->values()` and add DISTINCT to SQL query
- **Expected Improvement:** 10-50ms + bandwidth savings
- **Note:** May need to adjust subquery to dedupe before fetching messages

---

### Phase 2: Async Runtime Call (next sprint)

#### Step 2.1: Move wa-runtime Call to Queue
- **File:** Create new `app/Jobs/FetchWaRuntimeContactMeta.php`
- **Change:** Queue the metadata fetch instead of blocking on it
- **Expected Improvement:** **500ms-2s reduction** (main bottleneck!)
- **Approach:**
  1. Return base inbox immediately with null/placeholder metadata
  2. Queue async job to fetch contact metadata
  3. Frontend polls or uses WebSocket to get updated metadata
  4. OR use API endpoint to fetch metadata separately after inbox loads

---

### Phase 3: Additional Optimizations

- **Caching:** Add Redis cache for contact metadata (TTL: 5-10 min)
- **Indexing:** Verify `(conversation_id, created_at)` composite index exists
- **Pagination:** Consider limit 50 instead of 200 (more UI-friendly)
- **Lazy Loading:** Load profile photos only for visible conversations

---

## Implementation Priority

1. **URGENT** - Step 1.1: Replace MAX(id) subquery → 200-300ms gain
2. **HIGH** - Step 2.1: Async runtime call → 500ms-2s gain
3. **MEDIUM** - Step 1.2: Combine marks query → 50-100ms gain
4. **LOW** - Step 1.3: Fix deduplication → 10-50ms gain

---

## Testing Strategy

### Before Optimization
```bash
# Test with 100+ conversations
# Measure via MySQL slow query log or Laravel Debugbar
php artisan tinker
> now(); // Mark time
> // Load inbox
> now(); // Mark time
```

### After Each Optimization
- Compare query execution time
- Compare total response time
- Verify data integrity (marks, owner, latest message)
- Check for N+1 queries remaining

### Database Testing
```sql
-- Check slow queries
SHOW VARIABLES LIKE 'slow_query_log%';
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.1;

-- Monitor with
tail -f /var/log/mysql/mysql-slow.log
```

---

## Rollback Plan

Each optimization is independent and can be reverted:
1. Keep original code in comments or git branch
2. Test each change in staging first
3. Use feature flags if needed (unlikely - changes are low-risk)

---

## Success Metrics

| Metric | Current | Target | Status |
|--------|---------|--------|--------|
| Inbox load time | 1-3s | <500ms | TBD |
| DB query time | 300-700ms | <200ms | TBD |
| Runtime call latency | 500ms-2s | 0ms (async) | TBD |
| Maximum inbox size | 50/200 | No limit | TBD |

---

## Notes

- Database is MySQL 8.0+, supports window functions
- `wa_caraka_messages` has indexes on: `conversation_id`, `created_at`, `direction`, `remote_number`
- Subqueries in WHERE can be slow; prefer JOINs when possible
- wa-runtime service is external HTTP API - blocking calls are expensive
