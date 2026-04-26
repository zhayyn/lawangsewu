# Lawangsewu - Quick Reference Guide

**Last Updated:** April 21, 2026

## Project At a Glance

| Aspect | Details |
|--------|---------|
| **Type** | Laravel 13 court management portal |
| **Client** | PA Semarang (Islamic Court) |
| **Core Features** | WhatsApp gateway, case tracking, queues, visitor management |
| **Tech Stack** | Laravel 13, Vue 3, Inertia.js, Vite, Tailwind, Redis |
| **Database** | MySQL (local) + SIPP (remote @ 192.168.88.10) |
| **Integrations** | WA Caraka runtime, SIPP database, Google OAuth |
| **Completion** | 4 sprints, all features operational |

---

## Folder Structure Quickmap

```
app/
├── Models/          [25 models: User, WaCaraka*, Queue*, etc.]
├── Http/Controllers/[17+ controllers: Portal, WaCaraka, etc.]
├── Services/        [12 services: WaCaraka, SIPP, etc.]
├── Jobs/            [Queue jobs: SendWaCarakaOutboundMessage]
├── Events/          [Real-time events: WaCaraka*, Chat*]
└── Core/Traits/     [HasRolesAndPermissions]

config/
├── sipp.php         [SIPP database config]
├── wa_caraka.php    [WhatsApp runtime config]
├── services.php     [Google OAuth]
└── auth.php         [Auth config]

routes/
├── api.php          [API endpoints + webhooks]
└── web.php          [Portal + legacy widgets (100+ endpoints)]

database/migrations/
└── [45 migrations for 30+ tables]

resources/
├── js/              [Vue 3 components]
├── css/             [Tailwind + custom styles]
└── views/           [Inertia/Blade templates]
```

---

## Key Models by Function

### User & Access Control
- `User` - Core user model with roles
- `RolePermission` - Role-to-permission mapping
- `FeaturePermission` - Feature flags per role/user
- `LoginHistory` - Login audit trail
- `PermissionAuditLog` - Access change log

### WhatsApp/Messaging
- `WaCarakaMessage` - Individual messages (inbound/outbound)
- `WaCarakaConversation` - Conversation threads with owner
- `WaCarakaHandover` - Conversation transfer requests
- `WaCarakaTicket` - Support tickets (4 types: pengaduan, konsultasi, umum, live_konsul)
- `WaCarakaMenu` - Chatbot menu commands
- `WaCarakaSession` - Multi-step input state
- `WaCarakaSetting` - Module configuration
- `WaCarakaDailyMetric` - Analytics
- `WaCarakaSyncRun` - Sync operation tracking

### Queue Management
- `ServiceGroup` - Queue categories
- `QueueService` - Services within groups
- `ServiceCounter` - Physical/virtual counters
- `QueueTicket` - Core ticket model (used by all queues)
- `PtspQueueTicket` - PTSP variant
- `SidangQueueTicket` - Hearing queue variant

### Other Core
- `GuestbookEntry` - Visitor records
- `ChatMessage` - Internal messaging
- `CctvCamera` - Camera configuration
- `SippCache` - SIPP query result cache

---

## Controllers & Their Jobs

| Controller | Routes | Key Methods |
|-----------|--------|------------|
| `PortalController` | `/dashboard`, `/cctv`, `/chat`, `/pilar` | dashboard(), cctv(), chat(), pilar() |
| `WaCarakaController` | `/wa-caraka` | index(), reports(), handover management |
| `GuestbookController` | `/buku-tamu` | form(), store(), listing(), detail() |
| `PtspQueueController` | `/antrian-ptsp` | index() |
| `SidangQueueController` | `/antrian-sidang-v2` | index() |
| `SippHubController` | `/sipp-hub` | index(), refreshCache() |
| `ChatController` | `/chat` | index(), store(), destroy() |
| `WaCarakaWebhookController` | `/wa-caraka/webhook/*` | inbound(), historySync() |
| `WidgetCompatController` | 50+ legacy routes | apiPengumuman(), apiStatistik(), phpPublic() |
| `HealthCheckController` | `/health`, `/ready`, `/live` | health(), ready(), live() |

---

## Services & Their Responsibilities

### Core Services

| Service | Responsibility | Key Methods |
|---------|-----------------|------------|
| `WaCarakaService` | WhatsApp runtime bridge | get(), post(), getConversations(), sendMessage(), stats() |
| `WaCarakaChatbotService` | Auto-reply logic | processInbound(), buildDefaultMenu() |
| `WaCarakaConversationService` | Conversation lifecycle | handleInbound(), claimConversation(), requestHandover() |
| `SippService` | Hardened SIPP queries | query(type, input, phone) |
| `GoogleIdTokenVerifier` | OAuth token validation | verify(token) |
| `HealthCheckService` | System readiness | check() |
| `TailscaleService` | VPN integration | getStatus() |
| `SystemMonitorService` | Performance monitoring | getMetrics() |
| `PilarQueueAuthority` | Queue integration | getQueueInfo() |

---

## Database Tables Reference

### User Management (5 tables)
- `users` - Core user accounts
- `role_permissions` - Role-to-permission mapping
- `feature_permissions` - Feature flags
- `login_histories` - Login audit
- `permission_audit_logs` - Access change log

### WhatsApp/Messaging (10 tables)
- `wa_caraka_messages` - Messages
- `wa_caraka_conversations` - Conversation threads
- `wa_caraka_handovers` - Handover requests
- `wa_caraka_tickets` - Support tickets
- `wa_caraka_menus` - Menu config
- `wa_caraka_sessions` - Session state
- `wa_caraka_logs` - Message logs
- `wa_caraka_settings` - Settings
- `wa_caraka_daily_metrics` - Analytics
- `wa_caraka_sync_runs` - Sync tracking

### Queue Management (7 tables)
- `service_groups` - Group definitions
- `queue_services` - Service definitions
- `service_counters` - Counter definitions
- `queue_tickets` - Core tickets
- `ptsp_queue_tickets` - PTSP variant
- `sidang_queue_tickets` - Hearing variant

### Other (8 tables)
- `guestbook_entries` - Visitors
- `guestbook_settings` - Settings
- `messages` - Internal chat
- `chat_aliases` - User aliases
- `cctv_cameras` - Cameras
- `sipp_caches` - SIPP cache
- `google_access_allowlist` - OAuth whitelist

---

## Route Groups & Protection

```
Public (no auth):
  GET /health, /ready, /live
  POST /wa-caraka/webhook/* (token verified)
  GET /api/* (widget endpoints, throttled 60/min)

Protected (auth + verified + active + role check):
  GET /lawangsewu/dashboard
  GET /lawangsewu/chat
  GET /lawangsewu/buku-tamu
  GET /lawangsewu/antrian-ptsp
  GET /lawangsewu/sipp-hub
  etc.

Admin Only:
  GET /lawangsewu/admin/*
  POST /wa-caraka/admin/*
```

---

## External Service Integration Points

### 1. WA Caraka Runtime
**Type:** REST API (Node.js/Baileys)  
**URL:** `http://127.0.0.1:8790` (env: `LW_WA_V2_BASE`)  
**Auth:** Optional token header `X-WA-V2-Token`  
**Timeout:** 20 seconds

**Used By:**
- WaCarakaService - HTTP bridge
- Controllers - message sending/fetching
- Chatbot - contact metadata

**Webhooks (received):**
- POST /wa-caraka/webhook/inbound - new messages
- POST /wa-caraka/webhook/history-sync - sync history

### 2. SIPP Database
**Type:** MySQL database (read-only)  
**Host:** `192.168.88.10` (env: `SIPP_DB_HOST`)  
**Database:** `sipp`  
**Timeout:** 3s connect, 5s read

**Used By:**
- SippService - query cases, schedules
- WaCarakaChatbotService - delegate SIPP queries

**Security:** Parameterized queries, rate limiting, input validation

### 3. Google OAuth
**Type:** OAuth 2.0  
**Used For:** User authentication, optional SSO

**Credentials:** 
- GOOGLE_CLIENT_ID
- GOOGLE_CLIENT_SECRET
- GOOGLE_REDIRECT_URI

**Verification:** GoogleIdTokenVerifier service

---

## Authentication & Roles

### Role Hierarchy
```
superadmin (special flag, not in hierarchy)
  ↓
admin (4)
  ↓
useradmin (3)
  ↓
operator (2)
  ↓
viewer (1)
```

### Permission System
**Two-level:**
1. Role permissions (upper bound)
2. User overrides (further restrict only)

**Checks:**
- `user.isSuperAdmin()` - bypass all checks
- `user.hasPermission(name)` - single permission
- `user.hasAnyPermission(array)` - any permission
- `user.hasAllPermissions(array)` - all permissions
- `user.hasRole(role)` - specific role
- `user.isAtLeast(role)` - hierarchy check

---

## Key Business Flows

### WA Message Flow (Simplified)
```
Inbound WhatsApp Message
  ↓ (via webhook)
WaCarakaWebhookController::inbound()
  ↓
WaCarakaService::handleInbound()
  - Save to wa_caraka_messages
  - Find/create conversation
  - Increment unread count
  ↓
WaCarakaChatbotService::processInbound()
  - Check session state
  - Match menu command
  - Send auto-reply (if applicable)
  ↓
Queue job: SendWaCarakaOutboundMessage
  - Send to runtime
  - Log to wa_caraka_logs
  ↓
Real-time event broadcast (WebSocket)
```

### Conversation Claim Flow
```
New inbound message
  ↓
claimed_by == null?
  ├─ Yes: status = "pending"
  ├─ No: status = "open" (already owned)
  
First reply from operator
  ↓
Conversation::claimFor(operator)
  - claimed_by = operator.id
  - claimed_at = now()
  - status = "open"
  
Request handover
  ↓
WaCarakaHandover::create(...)
  - requested_by = A, requested_to = B (current owner)
  - status = "pending"
  
B approves
  ↓
handover.approve()
  - conversation.transferTo(A)
  - conversation.claimed_by = A.id
```

### SIPP Query Flow
```
User sends case number via WhatsApp
  ↓
WaCarakaChatbotService detects SIPP command
  ↓
SippService::query(type, input, fromNumber)
  ├─ Rate limit check (5/2min per number)
  ├─ Input validation (regex whitelist)
  ├─ Connectivity check (192.168.88.10:3306)
  ├─ Execute PDO parameterized query
  ├─ Cap results (15 rows max)
  └─ Format as user-friendly Indonesian text
  ↓
Return auto-reply string
```

---

## Important Configuration Files

| File | What to Configure |
|------|------------------|
| `.env` | DB, SIPP, WA runtime URLs, OAuth, Redis |
| `config/sipp.php` | SIPP connection, cache TTL |
| `config/wa_caraka.php` | Runtime URL, token, timeouts, limits |
| `config/services.php` | Google OAuth credentials |
| `config/auth.php` | Guard, super_admin_email |
| `config/database.php` | Database connections (local + sipp) |

---

## Middleware Stack

All protected routes use:
```
auth                    - Verify passport token
verified                - Check email_verified_at
active                  - Check is_active = true
role:viewer,operator... - Verify user role
throttle:60,1          - Rate limit (60/minute)
```

---

## Real-Time Updates (WebSocket)

**Broadcaster:** Reverb (or Pusher)

**Channels:**
- `wa-caraka.operator.{userId}` - Personal inbox updates
- `wa-caraka.conversation.{conversationId}` - Conversation updates

**Events Broadcast:**
- `WaCarakaMessageReceived` - New message
- `WaCarakaConversationUpdated` - Conversation state changed
- `WaCarakaMessageSynced` - Message delivery confirmed

**Vue Component Listeners:** Use Laravel Echo to subscribe and update UI in real-time

---

## Logging & Monitoring

### Log Files
- `storage/logs/laravel.log` - Application logs
- Database: `login_histories`, `permission_audit_logs`, `wa_caraka_logs`

### Health Checks
- `/health` - Basic health status
- `/ready` - Readiness check (all dependencies)
- `/live` - Liveness check (service responding)

### Metrics
- WaCarakaDailyMetric - Daily message stats
- WaCarakaSyncRun - Sync operation tracking

---

## Performance Optimization

### Caching Strategy
- **Redis:** Sessions, cache, queue
- **SIPP Cache:** TTL 15 minutes per query
- **Page Cache:** Guestbook settings (15 minutes)
- **N+1 Prevention:** Eager load relationships

### Rate Limiting
- Global: 60 requests/minute for API
- SIPP: 5 queries/2 minutes per phone number
- WA Message: Queued async, not real-time blocking

### Database Indexes
- `wa_caraka_conversations`: (remote_number, status, claimed_by, last_activity_at)
- `queue_tickets`: (ticket_date, status, service_id)
- `wa_caraka_messages`: (conversation_id, direction)

---

## Common Development Tasks

### Add a New Feature
1. Create migration (`php artisan make:migration`)
2. Create model (`php artisan make:model`)
3. Create controller (`php artisan make:controller`)
4. Create service (manual)
5. Add routes in `routes/web.php` or `routes/api.php`
6. Create Vue component in `resources/js/`

### Add Permission to Role
1. Edit `RolePermission` via admin UI
2. Or run seeder to populate permissions
3. Use `FeaturePermission` for feature flags

### Debug SIPP Query
1. Enable logging in SippService
2. Check rate limit cache: `redis-cli KEYS sipp_query_limit:*`
3. Verify connectivity: `mysql -h 192.168.88.10 -u admin sipp`
4. Test input validation with regex: `/^[\d\w\/\.\-\s]+$/u`

### Test WA Caraka
1. Verify runtime running: `curl http://127.0.0.1:8790/health`
2. Check queue processing: `php artisan queue:work`
3. Monitor WebSocket: Chrome DevTools → Network → WS
4. Check logs: `tail storage/logs/laravel.log`

---

## Critical Dependencies

**External:**
- WA Caraka Runtime (Node.js @ 127.0.0.1:8790)
- SIPP Database (MySQL @ 192.168.88.10:3306)
- Google OAuth (for authentication)
- Redis (cache, sessions, queue)
- WebSocket server (Reverb or Pusher)

**Can degrade gracefully:**
- SIPP: Returns offline message if unreachable
- WA Runtime: Message queued, retried on reconnect
- WebSocket: Falls back to polling if unavailable

---

## File Locations Quick Index

| What | Where |
|------|-------|
| Database schemas | `database/migrations/*.php` |
| Models | `app/Models/*.php` |
| Controllers | `app/Http/Controllers/` |
| Services | `app/Services/*.php` |
| Routes (API) | `routes/api.php` |
| Routes (Web) | `routes/web.php` |
| Vue components | `resources/js/` |
| Configuration | `config/` |
| Views/Inertia | `resources/views/` |

---

**Version:** 1.0  
**Last Updated:** April 21, 2026  
**For detailed architecture, see:** ARCHITECTURE_OVERVIEW.md
