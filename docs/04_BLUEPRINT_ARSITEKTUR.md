# 04 — Blueprint Arsitektur Teknis
# Lawangsewu V2 — Technical Architecture Blueprint

> **Versi:** 2.0  
> **Dibuat:** 2026-05-08 (rekonstruksi dari kondisi aktual)  

---

## 1. Diagram Arsitektur Tingkat Tinggi

```
┌─────────────────────────────────────────────────────────────────┐
│                     LAWANGSEWU SYSTEM                          │
│                                                                 │
│  ┌──────────┐   HTTPS    ┌──────────────────────────────────┐  │
│  │ Browser  │ ─────────► │      Nginx Reverse Proxy         │  │
│  │ (Staff)  │ ◄───────── │    lawangsewu.pa-semarang.go.id  │  │
│  └──────────┘            └──────────────┬───────────────────┘  │
│                                         │                       │
│  ┌──────────┐   HTTP     ┌──────────────▼───────────────────┐  │
│  │ External │ ─────────► │         PHP-FPM 8.3               │  │
│  │ Widgets  │            │      (Laravel 11 App)             │  │
│  └──────────┘            │                                   │  │
│                          │  ┌────────────┐ ┌─────────────┐  │  │
│  ┌──────────┐   WSS      │  │   MySQL    │ │ File Cache  │  │  │
│  │ Browser  │ ─────────► │  │  Database  │ │  Storage    │  │  │
│  │ (Staff)  │ ◄───────── │  └────────────┘ └─────────────┘  │  │
│  └──────────┘  Reverb    │                                   │  │
│                :8080     │  ┌────────────┐ ┌─────────────┐  │  │
│                          │  │   Queue    │ │   Reverb    │  │  │
│                          │  │  Worker    │ │ WS Server   │  │  │
│                          │  │ (database) │ │  :8080      │  │  │
│                          │  └────────────┘ └─────────────┘  │  │
│                          └──────────────────────────────────┘  │
│                                    │                            │
│                          ┌─────────▼──────────────────────┐   │
│                          │    WA Bridge (Server 33 LAN)   │   │
│                          │   http://192.168.88.33:8790    │   │
│                          │        Node.js / Baileys       │   │
│                          └─────────────────────────────────┘   │
│                                    │                            │
│                          ┌─────────▼──────────────────────┐   │
│                          │    WhatsApp Cloud / Mobile     │   │
│                          └─────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Request Flow

### 2.1 Request Web Normal (Inertia.js)

```
Browser ─► Nginx ─► PHP-FPM ─► Laravel Router ─► Middleware Stack
                                                         │
                    ┌────────────────────────────────────┘
                    │
                    ▼
              Controller ─► Service ─► Model ─► MySQL
                    │
                    ▼
              Inertia Response (JSON/HTML)
                    │
                    ▼
              Vue 3 (Client-side hydration)
```

### 2.2 Request API WaCaraka (Proxy Pattern)

```
Frontend (callApi) ─► POST /wa-caraka/api/{action}
                              │
                    WaCarakaController::proxy()
                              │
                    match($action) ─► private method
                              │
                    WaCarakaService / ConversationService
                              │
                    ┌─────────┴──────────┐
                    │                    │
                    ▼                    ▼
              MySQL DB          HTTP::post(bridge)
              (local state)   192.168.88.33:8790
```

### 2.3 Real-time Flow (WebSocket)

```
WA Bridge ─► POST /api/wa-caraka/webhook/inbound
                    │
              WaCarakaWebhookController
                    │
              Simpan ke MySQL
                    │
              Fire Event (WaCarakaMessageReceived)
                    │
              Reverb WebSocket Server (:8080)
                    │
              Browser (Echo.js listener)
                    │
              Vue reactive state update
                    │
              UI update (tanpa reload)
```

---

## 3. Database Schema Blueprint

### Core Tables

```sql
-- Pengguna sistem
users (id, name, email, alias, role, is_active, google_id, ...)

-- Permissions granular per user/role
feature_permissions (id, user_id, role, feature_key, is_allowed)
role_permissions (id, role, feature_key, is_allowed)
google_access_allowlist (id, email, notes)
login_histories (id, user_id, ip, user_agent, created_at)
permission_audit_logs (id, admin_id, target_user_id, action, ...)
```

### WaCaraka Tables

```sql
-- Percakapan (satu per nomor WA)
wa_caraka_conversations (
  id, conversation_id, remote_number, status, owner_id,
  ownership, last_message_at, closed_at, ...
)

-- Pesan (inbound & outbound)
wa_caraka_messages (
  id, conversation_id, direction, remote_number, local_number,
  message_text, message_type, wa_message_id, status,
  user_id, metadata, replied_at, created_at
)

-- Label/alias kontak oleh operator
wa_caraka_conversation_marks (
  id, wa_caraka_conversation_id, user_id, label, tone, note, is_pinned
)

-- Handover antar operator
wa_caraka_handovers (
  id, conversation_id, from_user_id, to_user_id, reason,
  status, requested_at, resolved_at
)

-- Log kirim pesan outbound (legacy)
wa_caraka_logs (id, sender, receiver, message, status, sent_at)

-- Tiket bantuan/pengaduan
wa_caraka_tickets (id, type, status, conversation_id, user_id, ...)

-- Metrik harian (aggregat)
wa_caraka_daily_metrics (id, date, metric_key, value)
wa_caraka_monthly_snapshots (id, month, metric_key, value)

-- Sesi perangkat
wa_caraka_sessions (id, session_key, data, ...)
wa_caraka_settings (id, key, value)
wa_caraka_sync_runs (id, started_at, finished_at, messages_synced)
wa_caraka_menus (id, trigger, response, is_active)
```

### Service Tables

```sql
-- Antrian PTSP
ptsp_queue_tickets (id, number, service_id, status, called_at, ...)
queue_tickets (id, number, queue_service_id, status, ...)
queue_services (id, name, prefix, counter_count, ...)
service_counters (id, service_id, counter_number, current_ticket_id, ...)
service_groups (id, name, ...)

-- Antrian Sidang
sidang_queue_tickets (id, nomor, ruang, jenis, status, ...)

-- TDMS
tdms_assets (id, name, category_id, serial_number, qr_token, status, ...)
tdms_categories (id, name, parent_id, ...)
tdms_maintenance_schedules (id, asset_id, scheduled_at, ...)
tdms_service_records (id, asset_id, technician, notes, serviced_at, ...)
tdms_replacements (id, asset_id, component, replaced_at, ...)

-- SIPP Cache
sipp_caches (id, cache_key, data, fetched_at)

-- CCTV
cctv_cameras (id, name, url, location, is_active, ...)

-- Buku Tamu
guestbook_entries (id, name, institution, purpose, status, ...)
guestbook_settings (id, key, value)

-- Chat Internal
chat_messages (id, user_id, text, media_path, ...)
chat_aliases (id, user_id, alias)

-- Widget
widget_visitors (id, page, ip, ...)

-- OAuth2 (Laravel Passport)
oauth_clients, oauth_access_tokens, oauth_auth_codes,
oauth_refresh_tokens, oauth_device_codes
```

---

## 4. Service Layer Blueprint

```
WaCarakaService            — Gateway HTTP ke WA Bridge, logging, statistik
WaCarakaConversationService — Logika percakapan (close, reopen, handover)
WaCarakaTicketService       — Manajemen tiket bantuan
WaCarakaChatbotService      — Logika auto-reply / chatbot
SippService                 — Ambil & cache data perkara dari SIPP
SystemMonitorService        — Health check semua komponen sistem
HealthCheckService          — Endpoint /health response
TailscaleService            — Info jaringan Tailscale
PilarQueueAuthority         — Logika antrian PILAR
OAuth2Service               — SSO Google + token management
GoogleIdTokenVerifier       — Verifikasi token Google ID
LegacyPendopoSyncService    — Sinkron buku tamu dari sistem lama
DocumentStylerService       — Generate laporan PDF
DatabaseOptimizationService — Optimasi & analisis query
DistributedTracingService   — Tracing request (optional)
ErrorTrackingService        — Error aggregation
PrometheusMetricsService    — Metrics export (optional)
RateLimitingService         — Rate limit custom
QueueMonitoringService      — Monitor job queue
```

---

## 5. Event & Realtime System

### Laravel Reverb (WebSocket)

```
Config: REVERB_APP_ID=lawangsewu
Host: 127.0.0.1:8080 (internal)
Exposed: wss://lawangsewu.pa-semarang.go.id/app/... (via Nginx)
```

### Events

```php
WaCarakaMessageReceived     // Pesan masuk baru dari WA
WaCarakaMessageSynced       // Pesan outbound terkonfirmasi
WaCarakaConversationUpdated // Status percakapan berubah
```

### Frontend Listener (Echo.js)
```js
Echo.private(`wa-caraka.user.${userId}`)
    .listen('WaCarakaMessageReceived', ...)
    .listen('WaCarakaConversationUpdated', ...);
```

---

## 6. Authentication Flow

```
1. User buka lawangsewu.pa-semarang.go.id
2. Redirect ke /login
3. Pilih:
   a) Login email + password  ─► Verify → Session
   b) Login Google SSO        ─► OAuth2 redirect → Google
                                       │
                              Google callback /auth/google/callback
                                       │
                              Cek google_access_allowlist
                                       │
                              Cek user is_active
                                       │
                              Session dibuat, redirect ke dashboard

4. Setiap request: middleware auth → verified → active → role
```

---

## 7. WaCaraka Bridge Protocol

### Endpoint yang digunakan

| Method | URL | Fungsi |
|---|---|---|
| GET | `/health` | Cek status koneksi device |
| GET | `/qr` | Ambil QR code untuk pairing |
| POST | `/restart` | Restart runtime |
| POST | `/reconnect` | Reconnect session |
| POST | `/disconnect` | Putus session |
| POST | `/send-text` | Kirim pesan teks |
| POST | `/send-media` | Kirim media |
| POST | `/unsend-message` | Recall pesan (≤60 menit) |
| GET | `/history` | Riwayat pesan runtime |
| POST | `/history/clear` | Hapus history runtime |
| GET | `/inbox` | Pull inbox dari runtime |
| POST | `/contacts/resolve` | Resolve info kontak |

### Authentication ke Bridge

```
Header: X-WA-V2-Token: {token dari config wa_caraka.token}
```

### Inbound Webhook (dari Bridge ke Laravel)

```
POST /api/wa-caraka/webhook/inbound
Header: Authorization: Bearer {token}
Body: { type, from, body, media, messageId, ... }
```

---

## 8. Deployment Configuration

### Process yang Harus Running

```bash
# 1. PHP-FPM (dikelola systemd)
sudo systemctl start php8.3-fpm

# 2. Laravel Reverb WebSocket
php artisan reverb:start --host=0.0.0.0 --port=8080

# 3. Queue Worker
php artisan queue:work database --queue=default \
  --sleep=1 --tries=3 --backoff=5 \
  --timeout=120 --max-jobs=500 \
  --max-time=3600 --memory=256

# 4. WA Bridge (Server 33 terpisah)
# Berjalan di 192.168.88.33:8790
```

### ⚠️ Rekomendasi: Setup Supervisor

```ini
[program:lawangsewu-reverb]
command=php /var/www/lawangsewu/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data

[program:lawangsewu-queue]
command=php /var/www/lawangsewu/artisan queue:work database --queue=default --sleep=1 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
```

### Cache & Config setelah Deploy

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

---

## 9. Security Model

| Layer | Mekanisme |
|---|---|
| Transport | HTTPS (SSL/TLS via Nginx) |
| Auth | Session-based, CSRF token |
| Authorization | Role + Feature permissions |
| Google SSO | ID token verification + allowlist |
| Bridge API | Bearer token header |
| Inbound webhook | Bearer token dari config |
| SQL injection | Eloquent ORM (parameterized) |
| XSS | Blade/Vue escaping by default |
| CSRF | Laravel CSRF middleware |
| Rate limiting | `throttle:60,1` pada API publik |

---

## 10. Konfigurasi Kunci (.env)

```ini
APP_ENV=production
APP_URL=https://lawangsewu.pa-semarang.go.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1

QUEUE_CONNECTION=database
CACHE_DRIVER=file
BROADCAST_DRIVER=reverb

REVERB_APP_ID=lawangsewu
REVERB_HOST=127.0.0.1
REVERB_PORT=8080

LW_WA_V2_BASE=http://192.168.88.33:8790
LW_WA_V2_TOKEN={secret_token}

# Google OAuth2
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=.../auth/google/callback
```
