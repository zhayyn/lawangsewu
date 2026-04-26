# Lawangsewu Platform - Complete Architecture Overview

**Document Date:** April 21, 2026  
**Status:** Comprehensive system analysis based on 30+ database tables, 25+ models, extensive controller/service layer  
**Intended Audience:** Development team, system architects, DevOps engineers

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Application Architecture](#application-architecture)
3. [Core Modules & Features](#core-modules--features)
4. [Database Schema & Relationships](#database-schema--relationships)
5. [Data Models & Relationships](#data-models--relationships)
6. [Controllers & Route Structure](#controllers--route-structure)
7. [Service Layer](#service-layer)
8. [External Service Integrations](#external-service-integrations)
9. [Authentication & Authorization](#authentication--authorization)
10. [Business Logic Flow](#business-logic-flow)
11. [Configuration & Environment](#configuration--environment)
12. [Frontend Architecture](#frontend-architecture)

---

## Project Overview

**Lawangsewu** is a Laravel-based government court system internal portal for PA Semarang (Pengadilan Agama Semarang - Islamic Court). It provides unified access to:

- **Employee Portal**: Dashboard, chat, CCTV monitoring, queue management
- **Case Management**: SIPP Hub integration for case tracking and scheduling
- **WhatsApp Messaging**: WA Caraka module for multi-operator message management
- **Queue Systems**: PTSP (administrative services) and Sidang (hearing) queue tracking
- **Visitor Management**: Guestbook system for visitor registration and tracking
- **Public Widgets**: Embeddable components for court websites/portals
- **Administrative Tools**: User access management, system monitoring, reporting

### Technology Stack

| Layer | Technology |
|-------|-----------|
| **Backend Framework** | Laravel 13 |
| **Frontend Framework** | Vue 3 + Inertia.js |
| **Styling** | Tailwind CSS 3 |
| **Build Tool** | Vite |
| **Database** | MySQL (primary), MySQL (SIPP remote) |
| **Caching** | Redis |
| **Authentication** | Laravel Passport (OAuth2) + Google OAuth |
| **Real-time** | Laravel Reverb (WebSocket) |
| **External Runtime** | Node.js (Baileys-based WA Caraka runtime) |

### Project Statistics

- **Migrations:** 45 database migrations
- **Models:** 25 core data models
- **Controllers:** 17 main controllers + admin/API variants
- **Services:** 12 business logic services
- **Routes:** 150+ API and web endpoints
- **Tables:** 30+ database tables

---

## Application Architecture

### Layered Architecture Pattern

```
┌─────────────────────────────────────────────────┐
│         Frontend Layer (Vue 3 + Inertia)        │
│  - Dashboard, Chat, Queues, SIPP Hub, etc.     │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│    Controller Layer (HTTP Request Handling)     │
│  - PortalController, WaCarakaController, etc.  │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│    Service Layer (Business Logic & Integration) │
│  - WaCarakaService, SippService, etc.          │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│       Model Layer (Data Access & ORM)           │
│  - User, WaCarakaConversation, QueueTicket     │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│      Database Layer (MySQL + External APIs)     │
│  - Local DB, SIPP DB (192.168.88.10), WA API  │
└─────────────────────────────────────────────────┘
```

### Directory Structure

```
app/
├── Console/           # Artisan commands
├── Core/             # Core traits & utilities
│   └── Traits/
│       └── HasRolesAndPermissions.php
├── Events/           # Event classes for real-time updates
├── Exceptions/       # Custom exception classes
├── Http/             # HTTP layer
│   ├── Controllers/
│   │   ├── Admin/    # Admin-only controllers
│   │   ├── Api/      # API controllers
│   │   └── *.php     # Main controllers
│   ├── Middleware/
│   └── Requests/
├── Jobs/             # Queued jobs (async processing)
├── Models/           # Eloquent models (25 models)
├── Providers/        # Service providers
├── Rules/            # Validation rules
├── Services/         # Business logic services (12 services)
└── Support/          # Helper classes

database/
├── migrations/       # 45 database migrations
├── factories/        # Model factories for testing
└── seeders/          # Database seeders

routes/
├── api.php          # API routes (protected + webhooks)
├── web.php          # Web routes (portal + widgets)
├── auth.php         # Auth routes
├── channels.php     # Broadcasting channels
└── console.php      # Console routes

config/
├── app.php          # App configuration
├── auth.php         # Authentication config
├── sipp.php         # SIPP integration config
├── wa_caraka.php    # WhatsApp integration config
├── services.php     # External service credentials
└── ...

resources/
├── js/              # Vue components & frontend code
├── css/             # Tailwind CSS
└── views/           # Blade templates & Inertia pages
```

---

## Core Modules & Features

### 1. **WA Caraka Module** - WhatsApp Messaging Gateway

A comprehensive WhatsApp integration system with multi-operator support, conversation management, and automated chatbot responses.

**Key Features:**
- Inbound/outbound message routing
- Conversation ownership ("claiming") model
- Operator handover workflow
- Auto-reply chatbot (SIPP queries, menu navigation)
- Ticket generation for support requests
- Daily metrics and analytics
- Message logging and history

**Core Tables:**
- `wa_caraka_messages` - Individual messages
- `wa_caraka_conversations` - Conversation threads
- `wa_caraka_handovers` - Operator transfer requests
- `wa_caraka_tickets` - Support/consultation tickets
- `wa_caraka_menus` - Chatbot menu configuration
- `wa_caraka_sessions` - User session state for multi-step queries
- `wa_caraka_logs` - Legacy message logs
- `wa_caraka_settings` - Module configuration
- `wa_caraka_daily_metrics` - Performance analytics
- `wa_caraka_sync_runs` - Sync operation tracking

**Key Models:**
- `WaCarakaMessage` - Message records
- `WaCarakaConversation` - Conversation threads with ownership
- `WaCarakaHandover` - Conversation transfer requests
- `WaCarakaTicket` - Support tickets (pengaduan, konsultasi, umum, live_konsul)
- `WaCarakaMenu` - Chatbot menu entries
- `WaCarakaSession` - Multi-step input state
- `WaCarakaSetting` - Configuration
- `WaCarakaDailyMetric` - Analytics

**Architecture:**
```
Inbound WhatsApp (Baileys Runtime @ 127.0.0.1:8790)
         ↓
WaCarakaWebhookController
         ↓
WaCarakaService (HTTP bridge to runtime)
         ↓
WaCarakaChatbotService (auto-reply logic)
  - Menu matching & routing
  - Session state handling
  - SIPP query delegation
         ↓
SippService (for case queries)
         ↓
Database storage + Message queue
```

---

### 2. **SIPP Hub Module** - Judicial Case Management

Integration with SIPP (Sistem Informasi Pertanggungjawaban Perkara) - the judicial case tracking system. Provides cached access to case information, hearing schedules, and statistics.

**Key Features:**
- Read-only connection to SIPP database (192.168.88.10)
- Hardened query service with security layers
- Rate limiting per phone number (5 queries/2 minutes)
- Input validation & sanitization
- Result caching with TTL
- Support for multiple query types

**Query Types:**
- `status_perkara` - Case status
- `jadwal_sidang` - Hearing schedule
- `akta_cerai` - Divorce certificate
- `biaya_panjar` - Advance fee information
- `pembayaran` - Payment tracking

**Core Tables:**
- `sipp_caches` - Cached SIPP query results with TTL
- `sipp_cache` - Additional SIPP cache storage

**Key Models:**
- `SippCache` - Cache entry model with expiration

**Security Measures (SippService):**
1. Strict input validation (whitelist format, length cap 60 chars)
2. PDO parameterized queries (no string interpolation)
3. Read-only connection enforcement
4. Rate limiting (5 queries per 2 minutes per phone number)
5. Aggressive connection timeout (3 sec connect, 5 sec read)
6. Result size cap (max 15 rows)
7. No credential logging
8. User-facing errors hide internal schema

---

### 3. **Queue Management System** - PTSP & Sidang

Manages multiple queue types for administrative services and court hearings.

**PTSP Queue (Administrative Services):**
- Service groups & counters
- Daily ticket numbering
- Customer service tracking

**Sidang Queue (Hearing Queue):**
- Court hearing queue management
- Judge availability tracking

**Core Tables:**
- `service_groups` - Queue service categories
- `queue_services` - Individual services
- `service_counters` - Physical/virtual counters
- `queue_tickets` - Ticket records with lifecycle tracking
- `ptsp_queue_tickets` - PTSP-specific tickets
- `sidang_queue_tickets` - Hearing queue tickets

**Key Models:**
- `ServiceGroup` - Service categories
- `QueueService` - Service configuration
- `ServiceCounter` - Counter definitions
- `QueueTicket` - Core ticket model
- `PtspQueueTicket` - PTSP specialization
- `SidangQueueTicket` - Hearing specialization

**Ticket Lifecycle:**
```
Created → Waiting → Called → Service Started → Completed/Cancelled
                                    ↓
                    Track with called_at, service_started_at, completed_at
```

---

### 4. **Dashboard & Portal**

Central hub for system information and feature access.

**Features:**
- Real-time statistics (messages, conversations, tickets)
- Queue status displays
- CCTV feeds
- User notifications
- Navigation to all modules

**Core Table:** None (read-only aggregation of other data)

**Key Model:** None (uses other models)

---

### 5. **Guestbook (Buku Tamu) - Visitor Management**

Visitor registration and tracking system.

**Features:**
- Visitor profile capture (name, position, institution)
- Photo capture (base64 or file upload)
- Institution category selection
- Purpose tracking
- Check-in timestamp
- Visitor list with filtering by period

**Core Tables:**
- `guestbook_entries` - Visitor records
- `guestbook_settings` - Configuration

**Key Models:**
- `GuestbookEntry` - Visitor record
- `GuestbookSetting` - Settings configuration

---

### 6. **Chat System** - Internal Messaging

Staff-to-staff messaging for quick communication.

**Features:**
- Direct message between users
- Read/unread status
- Media attachment support
- Message history

**Core Tables:**
- `messages` - Message records
- `chat_aliases` - User display names

**Key Models:**
- `ChatMessage` - Message model
- `ChatAlias` - User alias/nickname

---

### 7. **CCTV & Monitoring**

Security camera management and monitoring.

**Features:**
- Camera configuration (zones, embedded iframes)
- Active/inactive status
- Featured camera highlighting
- Sort ordering

**Core Tables:**
- `cctv_cameras` - Camera configuration

**Key Models:**
- `CctvCamera` - Camera configuration model

---

### 8. **User Management & Access Control**

RBAC system with feature-level permissions.

**Roles:**
- `superadmin` - Full system access
- `admin` - Administrative functions
- `useradmin` - User management
- `operator` - Main operational role
- `viewer` - Read-only access

**Core Tables:**
- `users` - User accounts
- `role_permissions` - Role-to-permission mappings
- `feature_permissions` - Feature flags per role/user
- `login_histories` - Login audit trail
- `permission_audit_logs` - Permission change logs
- `google_access_allowlist` - Google OAuth allowlist

**Key Models:**
- `User` - User account with roles
- `RolePermission` - Role-to-permission mapping
- `FeaturePermission` - Feature access control
- `LoginHistory` - Login tracking
- `PermissionAuditLog` - Change audit trail
- `GoogleAccessAllowlist` - OAuth whitelist

---

### 9. **Public Widgets & Legacy Bridge**

White-label embeddable components and backward compatibility layer.

**Features:**
- RSS feeds for announcements
- Statistic widgets (case count, e-court data)
- Schedule displays
- Server status monitoring
- Multiple output formats (JSON, HTML, legacy PHP)

**Routes:** 50+ legacy widget endpoints for backward compatibility

---

## Database Schema & Relationships

### Core Entity Relationship Map

```
users (1) ──────────────────────→ (∞) wa_caraka_conversations (claimed_by)
       (1) ──────────────────────→ (∞) wa_caraka_messages (user_id)
       (1) ──────────────────────→ (∞) chat_messages (user_id, recipient_id)
       (1) ──────────────────────→ (∞) login_histories
       (1) ──────────────────────→ (∞) permission_audit_logs
       (1) ──────────────────────→ (∞) feature_permissions

service_groups (1) ──────────────→ (∞) queue_services (service_group_id)

queue_services (1) ──────────────→ (∞) service_counters (queue_service_id)
              (1) ──────────────→ (∞) queue_tickets (service_id)

service_counters (1) ────────────→ (∞) queue_tickets (counter_id)

queue_tickets (1) ───────────────→ (1) queue_services (service_id)
              (1) ───────────────→ (1) service_counters (counter_id)

wa_caraka_conversations (1) ─────→ (∞) wa_caraka_messages (conversation_id)
                       (1) ─────→ (∞) wa_caraka_handovers (conversation_id)
                       (1) ─────→ (1) users (claimed_by → owner)

wa_caraka_handovers (1) ─────────→ (1) wa_caraka_conversations
                    (1) ─────────→ (1) users (requested_by)
                    (1) ─────────→ (1) users (requested_to)

wa_caraka_messages (1) ──────────→ (1) users

wa_caraka_tickets (1) ───────────→ (1) users (assigned_to)

guestbook_entries (string_pk)     [no foreign keys, standalone]

sipp_caches (data storage only)   [no foreign keys]

cctv_cameras (no relations)       [configuration table]
```

### Key Tables & Field Overview

| Table | Type | Purpose | Key Fields |
|-------|------|---------|-----------|
| `users` | Users | Core system users | id, email, name, role, google_id, is_active, is_superadmin |
| `wa_caraka_messages` | Messaging | Two-way messages | user_id, direction, remote_number, message_text, conversation_id, status |
| `wa_caraka_conversations` | Messaging | Conversation threads | conversation_id, remote_number, status, claimed_by, claimed_at, unread_count |
| `wa_caraka_handovers` | Workflow | Conversation transfers | conversation_id, requested_by, requested_to, status, force_approved |
| `wa_caraka_tickets` | Support | Tickets/requests | type, remote_number, assigned_to, status, message, reply |
| `wa_caraka_menus` | Config | Chatbot menu | command, label, type, response_text, sipp_query_type |
| `queue_tickets` | Operations | Queue tickets | ticket_date, ticket_number, service_id, counter_id, status, called_at, completed_at |
| `sipp_caches` | Caching | SIPP query cache | cache_key, data_type, data_content, expires_at, status |
| `guestbook_entries` | Visitors | Visitor records | name, position, institution, purpose, checkin |
| `feature_permissions` | Access Control | Feature flags | role_id, user_id, feature_key, enabled |

---

## Data Models & Relationships

### User Model Hierarchy

```php
User (extends Authenticatable)
├── Roles: superadmin, admin, useradmin, operator, viewer
├── Relationships:
│   ├── chatMessages() [HasMany via ChatMessage]
│   ├── waConversations() [HasMany via WaCarakaConversation]
│   ├── waMessages() [HasMany via WaCarakaMessage]
│   ├── waHandoverRequests() [HasMany via WaCarakaHandover]
│   ├── loginHistories() [HasMany]
│   └── featurePermissions() [HasMany]
├── Permissions:
│   ├── hasPermission(string)
│   ├── hasAnyPermission(array)
│   ├── hasAllPermissions(array)
│   ├── hasRole(string)
│   └── isAtLeast(string) - role hierarchy check
└── Super Admin:
    ├── isSuperAdmin() - direct flag OR configured email
    └── Always grants all permissions/roles
```

### WA Caraka Message Flow

```
WaCarakaMessage (individual message)
├── direction: "inbound" | "outbound"
├── status: "pending" | "sent" | "delivered" | "read" | "failed"
├── message_type: "text" | "image" | "document" | etc.
├── Relationships:
│   ├── user() - operator who sent (outbound) or assigned to (inbound)
│   └── conversation_id - link to WaCarakaConversation
├── Scopes:
│   ├── inbound/outbound
│   ├── today/forConversation/fromNumber
│   └── unreplied
└── Helpers:
    ├── isInbound() / isOutbound()
    └── conversation metadata
```

```
WaCarakaConversation (thread)
├── conversation_id - unique identifier
├── remote_number - remote party's phone
├── remote_name - display name
├── status: "pending" | "open" | "closed"
├── claimed_by - operator owner (User.id)
├── claimed_at - when claimed
├── unread_count - unread message badge
├── Relationships:
│   ├── owner() → User
│   ├── messages() → [WaCarakaMessage]
│   ├── handovers() → [WaCarakaHandover]
│   └── pendingHandover() → WaCarakaHandover?
├── Scopes: open(), pending(), closed(), claimedBy()
├── Helpers:
│   ├── isUnclaimed()
│   ├── isClaimedBy(userId)
│   ├── claimFor(User)
│   ├── transferTo(User)
│   ├── recordInbound()
│   └── markRead()
└── Workflow:
    pending (new, no reply yet)
      ↓
    open (claimed by operator, active)
      ↓
    closed (conversation done)
    
    [handovers occur during open state]
```

```
WaCarakaHandover (transfer request)
├── conversation_id - which conversation
├── requested_by - operator A (initiator)
├── requested_to - operator B (current owner)
├── status: "pending" | "approved" | "rejected" | "cancelled"
├── force_approved - admin override flag
├── decided_at - when decided
├── Relationships:
│   ├── conversation() → WaCarakaConversation
│   ├── requestor() → User (requested_by)
│   └── owner() → User (requested_to)
├── Helpers:
│   ├── isPending()
│   ├── approve() - updates conversation.claimed_by
│   ├── reject()
│   └── cancel()
└── Approval Flow:
    pending (A asks B for handover)
      ↓
    approved (B accepts, A gets conversation)
    OR
    rejected (B declines)
    OR
    cancelled (A cancels request)
```

### Queue System Models

```
ServiceGroup
├── code - group identifier (e.g., "ADMIN")
├── name - display name
└── services() → [QueueService]

QueueService
├── service_group_id → ServiceGroup
├── code - unique service code
├── name - display name
├── queue_prefix - e.g., "A"
├── numbering_scope - "service_daily", etc.
├── is_active
├── display_order
├── counters() → [ServiceCounter]
└── tickets() → [QueueTicket]

ServiceCounter
├── queue_service_id → QueueService
├── code - counter identifier
├── name - display name
├── call_label - announcement format
├── display_label - screen display format
├── location_type - "loket", "ruang", etc.
├── is_active
├── sort_order
└── tickets() → [QueueTicket]

QueueTicket
├── ticket_date
├── ticket_number - "001", "002", etc.
├── ticket_code - combined code
├── service_id → QueueService
├── counter_id → ServiceCounter
├── channel - "web", "kiosk", "mobile"
├── status - "waiting", "called", "serving", "completed", "cancelled"
├── customer_name
├── case_number / case_id_sipp - SIPP linkage
├── called_at / service_started_at / completed_at
├── payload_json - custom data
├── created_by / called_by / served_by - audit trail
└── notes
```

### SIPP Integration

```
SippCache (local caching layer)
├── cache_key - unique identifier
├── data_type - "case_statistics", "ecourt_statistics", etc.
├── data_content - JSON payload
├── cached_at
├── expires_at
├── status - "active", "expired"
└── Scopes: active(), byType()

SippService (hardened query service)
├── Security:
│   ├── Input validation (regex whitelist)
│   ├── PDO parameterized queries
│   ├── Read-only connection
│   ├── Rate limiting per phone (5/2min)
│   └── Connection timeout (3s connect, 5s read)
├── Methods:
│   ├── query(type, userInput, fromNumber)
│   ├── cekStatusPerkara(nomor)
│   ├── cekJadwalSidang(nomor)
│   ├── cekAktaCerai(nomor)
│   ├── cekBiayaPanjar(nomor)
│   └── cekPembayaran(nomor)
└── Result: User-facing string (Indonesian text)
```

---

## Controllers & Route Structure

### Main Controllers

| Controller | Purpose | Key Methods |
|-----------|---------|------------|
| `PortalController` | Main portal navigation | dashboard(), cctv(), chat(), pilar() |
| `WaCarakaController` | WhatsApp admin hub | index(), reports(), handover management |
| `GuestbookController` | Visitor management | form(), store(), listing(), detail() |
| `PtspQueueController` | PTSP queue display | index() |
| `SidangQueueController` | Hearing queue display | index() |
| `SippHubController` | SIPP case stats | index(), refreshCache() |
| `ChatController` | Internal messaging | index(), store(), destroy() |
| `WaCarakaWebhookController` | WhatsApp webhooks | inbound(), historySync() |
| `WidgetCompatController` | Legacy widget bridge | apiPengumuman(), apiStatistik(), etc. |
| `HealthCheckController` | System health monitoring | health(), ready(), live() |

### Admin Controllers

| Controller | Purpose |
|-----------|---------|
| `CctvCameraController` | Camera management |
| `WaCarakaAdminController` | WA configuration |
| `UserAccessController` | User access control |
| `SystemMonitorController` | System monitoring |
| `LaporanController` | Reporting |

### API Controller

| Controller | Purpose |
|-----------|---------|
| `PortalApiController` | Mobile/external API |

### Route Groups & Protection

```
/health, /ready, /live
  └─ Public (no auth)

/lawangsewu/api/*
  └─ [auth, verified, active, role:viewer,operator,useradmin,admin, throttle:60]

/lawangsewu/* (POST /chat/messages, etc.)
  └─ [auth, verified, active, role:operator,admin, throttle:30]

/api/* (legacy widget endpoints)
  └─ [throttle:60] - public with rate limiting

/wa-caraka/webhook/*
  └─ Public but verified by shared token

/lawangsewu/* (dashboard, chat, cctv, buku-tamu)
  └─ [auth, verified, active, role:viewer,operator,useradmin,admin]
```

---

## Service Layer

### WaCarakaService

**Purpose:** Bridge between controllers and WA Caraka runtime (Node.js @ 127.0.0.1:8790)

**Key Methods:**
- `baseUrl()` - runtime URL
- `broadcastLimit()` - max recipients per broadcast
- `get(path, query)` - HTTP GET to runtime
- `post(path, data)` - HTTP POST to runtime
- `getConversations()` - fetch all conversations
- `getMessages(conversationId)` - fetch messages in conversation
- `sendMessage(to, text, type)` - queue outbound message
- `stats()` - message statistics
- `messageStats()` - message type breakdown
- `validateRemoteNumber(number)` - number format validation

**HTTP Headers:**
- `Accept: application/json`
- `X-WA-V2-Token: [token from config]`

**Error Handling:** Soft fails with user-friendly messages

**Features:**
- Retry logic for transient failures
- Timeout handling (20 seconds)
- Request rate limiting (60 requests/minute)

---

### WaCarakaChatbotService

**Purpose:** Auto-reply logic for inbound messages

**Key Method:** `processInbound(from, text) → ?string`

**Flow:**
1. Check for active session (multi-step input)
2. Check if message matches menu command
3. Send default menu if no match

**Integration Points:**
- `WaCarakaSession` - multi-step state
- `WaCarakaMenu` - command routing
- `SippService` - delegate SIPP queries
- `WaCarakaTicket` - create tickets

**Menu Types:**
- `direct` - immediate reply
- `input` - request user input (2-stage)
- `prompt` - show sub-menu

**SIPP Query Delegation:**
Menu commands 7-11 delegate to SippService for case queries

---

### SippService

**Purpose:** Hardened read-only SIPP database query service

**Key Public Method:**
```php
query(string $queryType, string $userInput, string $fromNumber): string
```

**Query Types:**
- `status_perkara` - Case status
- `jadwal_sidang` - Hearing schedule  
- `akta_cerai` - Divorce certificate
- `biaya_panjar` - Advance fees
- `pembayaran` - Payments

**Security Implementation:**

1. **Rate Limiting:** 5 queries per phone number per 2 minutes
   - Uses Redis cache for tracking
   - Returns user-friendly message when exceeded

2. **Input Validation:**
   - Max length: 60 characters
   - Whitelist pattern: `/^[\d\w\/\.\-\s]+$/u`
   - Validates nominor perkara format

3. **Database Security:**
   - Connection name: `sipp` (separate config)
   - PDO parameterized queries only
   - Connection timeout: 3 seconds (connect), 5 seconds (read)
   - No credentials logged

4. **Result Constraints:**
   - Max rows: 15
   - Errors reveal nothing about schema
   - Offline messages pre-defined

5. **Logging:**
   - Only masked identifiers logged
   - Credentials never logged
   - User input is logged for analytics

---

### WaCarakaConversationService

**Purpose:** Conversation lifecycle management

**Key Methods:**
- `handleInbound(message)` - process inbound
- `claimConversation(conversationId, userId)` - operator claim
- `requestHandover(conversationId, fromUserId, toUserId)` - request transfer
- `approveHandover(handoverId)` - approve transfer
- `rejectHandover(handoverId)` - reject transfer
- `closeConversation(conversationId)` - mark done
- `getOperatorInbox(userId)` - conversations for operator

**Events Triggered:**
- `WaCarakaConversationUpdated`
- `WaCarakaMessageReceived`
- `WaCarakaMessageSynced`

---

### Other Services

| Service | Purpose |
|---------|---------|
| `HealthCheckService` | System readiness checks |
| `GoogleIdTokenVerifier` | Google OAuth validation |
| `TailscaleService` | Tailscale integration |
| `SystemMonitorService` | Performance monitoring |
| `PilarQueueAuthority` | PILAR queue integration |
| `DocumentStylerService` | Document formatting |
| `LegacyPendopoSyncService` | Legacy data migration |

---

## External Service Integrations

### 1. WA Caraka Runtime (Node.js/Baileys)

**Connection:** HTTP to `127.0.0.1:8790` (configurable via `wa_caraka.base_url`)

**Authentication:** Optional static token via `X-WA-V2-Token` header

**Protocol:**
- REST API over HTTP
- Timeout: 20 seconds
- Broadcast limit: 50 recipients per call

**Endpoints Used:**
- `GET /conversations` - fetch all conversations
- `GET /messages/:conversationId` - fetch conversation messages
- `POST /messages/send` - send message
- `GET /contacts/:number/metadata` - fetch contact metadata
- `POST /broadcast` - send bulk messages

**Webhook Endpoints (Lawangsewu):**
- `POST /wa-caraka/webhook/inbound` - inbound message webhook
- `POST /wa-caraka/webhook/history-sync` - sync historical messages

**Message Queue:** Outbound messages use Laravel Queue (job: `SendWaCarakaOutboundMessage`)

---

### 2. SIPP Database (Remote MySQL)

**Connection:** MySQL @ `192.168.88.10:3306` (configurable via env vars)

**Database:** `sipp`

**Credentials:** Via `SIPP_DB_USERNAME`, `SIPP_DB_PASSWORD`

**Connection Type:** Read-only (enforced by service layer)

**Tables Queried:**
- `perkara` - Case information
- `jadwal_sidang` - Hearing schedules
- `hakim` - Judge information
- `akta_cerai` - Divorce certificates
- `biaya` - Fee schedules

**Timeout:** 3 seconds connection, 5 seconds read

**Caching:** Results cached in `sipp_caches` table (TTL configurable via `SIPP_CACHE_TTL`)

---

### 3. Google OAuth

**Provider:** Google OAuth 2.0

**Configuration File:** `config/services.php`

**Credentials:**
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`

**Usage:**
- SSO authentication for users
- Optional allowlist via `google_access_allowlist` table

**Verification Service:** `GoogleIdTokenVerifier`

---

### 4. Legacy Widget System

**Architecture:** White-label, embeddable PHP components

**Served From:** `/widgets` directory

**Output Formats:**
- HTML (for embedded pages)
- JSON (for API consumers)
- PHP (legacy output)

**Notable Widgets:**
- Announcement (pengumuman)
- Court statistics (statistik-perkara)
- Hearing schedule (jadwal-persidangan)
- Queue status (antrian-ptsp, antrian-sidang)
- Judge statistics (statistik-hakim)
- E-court statistics (statistik-ecourt)

**Integration:** `WidgetCompatController` bridges modern framework with legacy code

---

## Authentication & Authorization

### Authentication Flow

```
Login Request
    ↓
Middleware: auth (Laravel Passport)
    ↓
Verified: email_verified_at (required)
    ↓
Active: is_active = true (required)
    ↓
Role Check: role in [viewer, operator, useradmin, admin]
    ↓
Throttle: request rate limiting
    ↓
Authorized
```

### Authorization Models

#### 1. Role-Based Access Control (RBAC)

**Roles (Hierarchy):**
```
viewer (level 1)
  ↑
operator (level 2)
  ↑
useradmin (level 3)
  ↑
admin (level 4)
  ↑
superadmin (level 5) - special flag
```

**Permission Query:**
```
User.role → RolePermission.role → Permission.name
```

**Methods (HasRolesAndPermissions trait):**
- `hasPermission(string)` - check single permission
- `hasAnyPermission(array)` - check any permission
- `hasAllPermissions(array)` - check all permissions
- `hasRole(string)` - check specific role
- `isAtLeast(role)` - check role hierarchy

---

#### 2. Feature Flags (FeaturePermission)

**Two-level system:**
1. Role access (upper bound)
2. User override (may only restrict)

**Query Hierarchy:**
```
1. Superadmin? → Always allow
2. Role allows? → Check role_id + feature_key
3. User override? → Check user_id + feature_key (further restrict only)
```

**Methods:**
```php
FeaturePermission::hasAccess(User|int, featureKey): bool
```

**Used For:**
- Menu item visibility per role
- Feature availability control
- Temporary user restrictions

---

#### 3. Permission Audit Trail

**Table:** `permission_audit_logs`

**Tracked Actions:**
- Permission changes
- Role assignments
- Feature flag toggles
- Superadmin grants

**Fields:**
- `user_id` - who made change
- `target_user_id` - whose access changed
- `action` - change type
- `before` - previous state
- `after` - new state
- `timestamp`

---

### Middleware Stack

| Middleware | Purpose | When Required |
|-----------|---------|---------------|
| `auth` | Verify passport token | All protected routes |
| `verified` | Check email_verified_at | Dashboard & sensitive features |
| `active` | Check is_active = true | All protected routes |
| `role:*` | Verify user's role | Route-specific |
| `throttle:60,1` | Rate limit (60/minute) | API & public endpoints |

---

## Business Logic Flow

### WA Message Flow (Complete Cycle)

```
1. INBOUND MESSAGE (from WhatsApp)
   ↓
   WaCarakaWebhookController::inbound()
   ├─ Verify webhook token
   ├─ Validate payload
   └─ Queue WaCarakaService::handleInbound()
   
2. INBOUND PROCESSING
   ↓
   WaCarakaService::handleInbound(inboundPayload)
   ├─ Extract message details (from, text, timestamp)
   ├─ Find or create WaCarakaConversation
   ├─ Create WaCarakaMessage record (inbound)
   ├─ Update conversation.last_activity_at
   ├─ Increment conversation.unread_count
   ├─ Check if conversation is claimed
   │  └─ If not claimed: emit WaCarakaConversationUpdated event
   ├─ Queue SendWaCarakaOutboundMessage job
   └─ Dispatch WaCarakaMessageReceived event
   
3. AUTO-REPLY CHATBOT (optional)
   ↓
   WaCarakaChatbotService::processInbound(from, text)
   ├─ Check WaCarakaSession (pending multi-step input)
   │  ├─ If active: handleSessionInput() → process answer
   │  └─ If complete: clear session, send reply
   ├─ Check WaCarakaMenu (command matching)
   │  ├─ If direct: return response_text
   │  ├─ If input: prompt for input, save session
   │  └─ If prompt: show sub-menu
   ├─ If menu matches SIPP query type:
   │  └─ Delegate to SippService::query()
   │     ├─ Validate user input (nomor perkara)
   │     ├─ Check rate limit (5/2min per from_number)
   │     ├─ Query SIPP database (192.168.88.10)
   │     └─ Return formatted user-facing string
   ├─ Create WaCarakaTicket if menu.creates_ticket_type set
   └─ Return auto-reply text (or null)
   
4. OUTBOUND MESSAGE SENDING
   ↓
   SendWaCarakaOutboundMessage job (queued)
   ├─ Prepare message (auto-reply or operator reply)
   ├─ Call WaCarakaService::sendMessage(to, text, type)
   │  └─ HTTP POST to runtime @ 127.0.0.1:8790
   ├─ Log result to wa_caraka_logs (if logging enabled)
   ├─ Update WaCarakaMessage.status
   └─ Emit WaCarakaMessageSynced event
   
5. OPERATOR REPLY (manual)
   ↓
   Operator types message in UI
   ├─ Validates input
   ├─ Creates WaCarakaMessage (outbound, user_id = operator)
   ├─ Queues SendWaCarakaOutboundMessage job
   ├─ Updates conversation.last_activity_at
   └─ Emits WaCarakaConversationUpdated event
   
6. REAL-TIME UPDATES (WebSocket via Reverb)
   ↓
   Broadcasting events to connected clients:
   ├─ WaCarakaMessageReceived
   ├─ WaCarakaConversationUpdated
   └─ WaCarakaMessageSynced
   
   Vue component listens and updates UI in real-time
```

---

### Conversation Claim Flow

```
New Inbound Message
    ↓
    Conversation.claimed_by == null?
    ├─ Yes: status = "pending", unread_count = 1
    │   └─ Emit WaCarakaConversationUpdated (waiting for first reply)
    └─ No: is operator notified?
        └─ Increment unread_count
        └─ Emit WaCarakaConversationUpdated
        
Operator Replies to Pending Conversation
    ↓
    WaCarakaConversation.claimFor(User)
    ├─ Set claimed_by = user.id
    ├─ Set status = "open"
    ├─ Set claimed_at = now()
    └─ Create outbound message (claim implicit)
    
Operator Requests Handover
    ↓
    WaCarakaHandover::create(...)
    ├─ requested_by = operator_a.id (initiator)
    ├─ requested_to = operator_b.id (current owner)
    ├─ status = "pending"
    └─ Notify operator_b
    
Conversation Owner Approves Handover
    ↓
    WaCarakaHandover::approve()
    ├─ Set status = "approved"
    ├─ Call conversation.transferTo(operator_a)
    │  └─ conversation.claimed_by = operator_a.id
    │  └─ conversation.claimed_at = now()
    ├─ Notify both operators
    └─ Emit WaCarakaConversationUpdated
    
Admin Force Takeover
    ↓
    WaCarakaHandover::forceApprove()
    ├─ Set force_approved = true
    ├─ Set status = "approved"
    └─ Same transfer process as above
```

---

### SIPP Query Flow

```
User sends "1234/Pdt.G/2024/PA.Smg"
    ↓
WaCarakaChatbotService detects SIPP command
    ↓
SippService::query('status_perkara', input, fromNumber)
    ├─ RATE LIMIT CHECK
    │  └─ Cache key: "sipp_query_limit:{fromNumber}"
    │  └─ Increment counter, TTL 2 min
    │  └─ If > 5: return "Terlalu banyak permintaan"
    │
    ├─ INPUT VALIDATION
    │  └─ Max length 60 chars
    │  └─ Whitelist pattern: /^[\d\w\/\.\-\s]+$/u
    │  └─ If invalid: return format error message
    │
    ├─ CONNECTIVITY CHECK
    │  └─ Ping 192.168.88.10:3306 (with 3 sec timeout)
    │  └─ If unreachable: return offline message
    │
    ├─ QUERY EXECUTION (PDO parameterized)
    │  └─ SELECT * FROM perkara WHERE nomor_perkara = ?
    │  └─ Timeout: 5 seconds
    │  └─ Max results: 15 rows
    │
    ├─ RESULT FORMATTING
    │  └─ Convert query results to user-friendly Indonesian text
    │  └─ Include all relevant case details
    │
    └─ RETURN user-facing string
        
Cache result (SippCache)
    └─ TTL: 15 minutes (via SIPP_CACHE_TTL)
    └─ Next query within TTL returns cached result
```

---

### Queue Ticket Lifecycle

```
Ticket Creation
    ├─ Manual (operator creates)
    ├─ Or auto-generated (kiosk/system)
    └─ Fields: date, number, service, counter, customer, case_number
    
Initial State: WAITING
    ├─ Ticket displayed in queue
    ├─ Customer waits
    └─ called_at = null
    
Called: CALLED
    ├─ Operator/system calls ticket
    ├─ called_at = now()
    ├─ called_by = operator.id
    └─ Customer approaches counter
    
Service Started: SERVING
    ├─ Operator begins service
    ├─ service_started_at = now()
    ├─ served_by = operator.id
    └─ Transaction in progress
    
Completed: COMPLETED
    ├─ Service finished
    ├─ completed_at = now()
    ├─ Customer leaves
    └─ Ticket archived
    
OR Cancelled: CANCELLED
    ├─ Customer didn't respond to call
    ├─ OR service could not be provided
    ├─ cancelled_at = now()
    └─ Reason in notes field
```

---

## Configuration & Environment

### Essential Environment Variables

**Database:**
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=lawangsewu
DB_USERNAME=...
DB_PASSWORD=...
```

**SIPP Integration:**
```
SIPP_ENABLED=true
SIPP_DB_HOST=192.168.88.10
SIPP_DB_PORT=3306
SIPP_DB_DATABASE=sipp
SIPP_DB_USERNAME=...
SIPP_DB_PASSWORD=...
SIPP_DB_TIMEOUT=5
SIPP_CACHE_TTL=15 (minutes)
```

**WA Caraka Runtime:**
```
LW_WA_V2_BASE=http://127.0.0.1:8790
LW_WA_V2_TOKEN=... (optional)
LW_WA_V2_TIMEOUT=20 (seconds)
LW_WA_BROADCAST_LIMIT=50
LW_WA_LOGGING=true
LW_WA_MAX_MEDIA_BYTES=15728640 (15MB)
WA_QUEUE=default (Laravel queue)
```

**Authentication:**
```
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=...
```

**Cache & Session:**
```
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

**Application:**
```
APP_NAME=Lawangsewu
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lawangsewu.example.com
```

### Config Files

| File | Purpose |
|------|---------|
| `config/app.php` | App name, timezone, locale, providers |
| `config/auth.php` | Authentication driver, guards, super_admin_email |
| `config/database.php` | Database connections (including sipp) |
| `config/cache.php` | Cache driver configuration |
| `config/queue.php` | Queue driver configuration |
| `config/sipp.php` | SIPP connection & cache settings |
| `config/wa_caraka.php` | WA runtime URL, token, timeout, limits |
| `config/services.php` | Google OAuth & other services |
| `config/features.php` | Feature flags configuration |
| `config/health_check.php` | Health check endpoints |

---

## Frontend Architecture

### Frontend Stack

- **Framework:** Vue 3 (Composition API)
- **Router:** Inertia.js (server-driven routing)
- **Styling:** Tailwind CSS 3
- **Build:** Vite
- **Components:** Lucide Vue (icons), Emoji Picker
- **Real-time:** Laravel Echo + Pusher/Reverb (WebSocket)

### Page Structure

All pages use Inertia.js server-side rendering:

```vue
<template>
  <Layout>
    <!-- Page-specific content -->
  </Layout>
</template>

<script setup>
// Props from controller
defineProps(['appMeta', 'navGroups', 'authUser', 'data'])
</script>
```

### Main Pages

| Route | Controller | Purpose |
|-------|-----------|---------|
| `/` | PortalController | Dashboard |
| `/dashboard` | PortalController | Dashboard variant |
| `/cctv` | PortalController | CCTV monitoring |
| `/chat` | ChatController | Internal messaging |
| `/buku-tamu` | GuestbookController | Visitor form & listing |
| `/antrian-ptsp` | PtspQueueController | PTSP queue display |
| `/antrian-sidang-v2` | SidangQueueController | Hearing queue display |
| `/sipp-hub` | SippHubController | Case statistics |
| `/wa-caraka` | WaCarakaController | WhatsApp inbox & control |

### Component Patterns

**Shared Layout:**
```
LawangsewuPortal.php provides:
- appMeta() - app name, version, branding
- navGroups() - navigation menu
- dashboardPayload() - dashboard data
- cctvPayload() - CCTV data
```

**Real-time Updates:**
- Event broadcasting via Laravel Echo
- WebSocket connection to Reverb
- Component listeners for:
  - WaCarakaMessageReceived
  - WaCarakaConversationUpdated
  - WaCarakaMessageSynced

---

## Summary of Key Architecture Insights

### Strengths

1. **Layered Architecture**
   - Clear separation: Controllers → Services → Models → DB
   - Service layer isolates business logic from HTTP concerns
   - Reusable services across controllers

2. **Security-First Design**
   - SIPP service implements hardened query patterns
   - Input validation, parameterized queries, rate limiting
   - Permission audit trail for compliance
   - Feature flags for fine-grained access control

3. **Scalable Messaging**
   - Queue-based async message sending
   - Conversation claim model prevents double-responses
   - Handover workflow for operator collaboration
   - Real-time updates via WebSocket

4. **Multi-System Integration**
   - Cleanly abstracted external services (WA Caraka, SIPP)
   - Fallback strategies for unavailable external systems
   - Caching strategy reduces external load

5. **Backward Compatibility**
   - 50+ legacy widget endpoints preserved
   - White-label embed system for partner portals
   - Migration from legacy wamehehe codebase intact

### Key Dependencies

- **External Services:**
  - WA Caraka Runtime (Node.js @ 127.0.0.1:8790)
  - SIPP Database (MySQL @ 192.168.88.10:3306)
  - Google OAuth

- **Infrastructure:**
  - Redis (caching, sessions, queue)
  - MySQL (primary database)
  - WebSocket server (Reverb or alternative)

### Critical Configurations

- SIPP connection timeout and credentials
- WA Runtime base URL and authentication token
- Rate limiting thresholds
- Cache TTL values
- Feature permission roles

---

**Document Version:** 1.0  
**Last Updated:** April 21, 2026  
**Author:** Architecture Analysis System
