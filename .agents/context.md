# 🏛️ Konteks Arsitektur Lawangsewu

> Dokumen ini adalah sumber kebenaran untuk semua agent SenopaTEA.
> Setiap agent WAJIB membaca file ini sebelum melakukan aksi apapun.
> **Terakhir diperbarui:** 2026-05-25

---

## Identitas Sistem

| Atribut | Nilai |
|---|---|
| **Nama** | Lawangsewu |
| **Organisasi** | Pengadilan Agama Semarang |
| **Deskripsi** | Portal digital terpadu: antrian, buku tamu, chat, CCTV, WA gateway, TDMS, monitoring |
| **Superadmin** | `dbprakom@gmail.com` |
| **Domain Production** | `lawangsewu.pa-semarang.go.id` |

---

## Tech Stack (LOCKED — Tidak Boleh Diubah)

> ⚠️ Versi di bawah adalah versi **aktual di composer.json / package.json**. Jangan asumsikan versi lain.

| Layer | Teknologi | Versi (composer/package.json) |
|---|---|---|
| **Framework** | Laravel | ^13.0 |
| **PHP** | PHP | ^8.3 |
| **Frontend** | Vue 3 + Inertia.js | ^3.4 / ^2.0 |
| **Styling** | Tailwind CSS | ^3.2 |
| **Auth SSO** | Google OAuth via Socialite | ^5.26 |
| **Auth API** | Laravel Sanctum | ^4.0 |
| **Auth OAuth2 Server** | Laravel Passport | ^13.7 |
| **Realtime** | Laravel Reverb | ^1.10 |
| **Realtime Client** | Laravel Echo + Pusher-JS | ^2.3 / ^8.5 |
| **Database** | MySQL | 8.x |
| **Test PHP** | PHPUnit | ^11.0 \| ^12.0 |
| **Test JS** | Vitest | ^2.1 |
| **Build** | Vite | ^6.0 |
| **Routing JS** | Ziggy | ^2.0 |
| **PDF** | barryvdh/laravel-dompdf | ^3.1 |
| **Word Export** | phpoffice/phpword | ^1.4 |
| **Markdown** | erusev/parsedown | ^1.8 |
| **Chart** | Chart.js | ^4.5 |
| **Icons** | lucide-vue-next | ^1.0 |

---

## Arsitektur Modul

```
Lawangsewu
├── 🏠 Dashboard          - Ringkasan seluruh modul + live stats
├── 📋 PTSP Queue         - Antrian Pelayanan Terpadu Satu Pintu
├── 📋 Pelayanan PTSP     - Manajemen loket & penyerahan AC/Salinan
├── ⚖️ Sidang Queue       - Antrian Persidangan
├── 📖 Pendopo            - Buku Tamu Digital (sinkronisasi dari legacy CI4)
├── 💬 Chat               - Komunikasi Internal (Reverb WebSocket)
├── 📺 CCTV               - Monitoring & manajemen kamera (rename, stream)
├── 📊 Pilar PASMG        - Hub Pilar Antrian Terpadu
├── 📡 SIPP Hub           - Integrasi data SIPP (read-only)
├── 💬 WA Caraka          - Gateway WhatsApp: inbox operator, chatbot, ticket, handover
├── 📁 TDMS               - Tool & Document Management System (aset, maintenance, servis)
├── 💼 PakPp              - Pengelolaan PAK/PP (dokumen Word export)
├── 🔧 Admin Panel        - Kelola user, kamera, pendopo, allowlist, permission
├── 🌐 Widget Compat      - Widget publik legacy (embed, RSS, dll)
├── 🛰️ Satellite          - Pendopo satelit (multi-lokasi)
└── 🌐 Omnichannel LiveChat - Live chat publik terintegrasi WaCaraka
```

---

## Autentikasi & Otorisasi

### SSO (Single Sign-On)
- Login **hanya via Google OAuth** (Socialite)
- Email harus terdaftar di `google_access_allowlist` atau match `SUPERADMIN_EMAIL`
- User baru → status `is_active = false` → perlu approval admin
- Superadmin auto-activate, auto-role `admin`

### API Auth (Dual Layer)
- **Sanctum:** Untuk session-based API (Inertia, internal endpoints)
- **Passport:** Untuk OAuth2 server (token issuance untuk integrasi eksternal)

### RBAC (Role-Based Access Control)
Hierarki: `viewer(1) → operator(2) → admin(3) → superadmin(4)`

| Role | Dashboard | View Queue | Operate Queue | Admin Panel | Manage Users |
|---|---|---|---|---|---|
| viewer | ✅ | ✅ | ❌ | ❌ | ❌ |
| operator | ✅ | ✅ | ✅ | ❌ | ❌ |
| admin | ✅ | ✅ | ✅ | ✅ | ❌ |
| superadmin | ✅ | ✅ | ✅ | ✅ | ✅ |

Middleware stack: `auth → verified → active → role:xxx`

---

## Struktur Database (Models)

> Total: **40 model aktif** per 2026-05-25

### Core & Auth
| Model | Tabel | Fungsi |
|---|---|---|
| `User` | `users` | User dengan role, is_active, is_superadmin, google_id |
| `GoogleAccessAllowlist` | `google_access_allowlist` | Email whitelist untuk login |
| `FeaturePermission` | `feature_permissions` | Definisi permission granular per fitur |
| `RolePermission` | `role_permissions` | Mapping role → permission |
| `PermissionAuditLog` | `permission_audit_logs` | Log perubahan permission |
| `LoginHistory` | `login_histories` | Riwayat login per user |

### Antrian
| Model | Tabel | Fungsi |
|---|---|---|
| `PtspQueueTicket` | `ptsp_queue_tickets` | Tiket antrian PTSP |
| `PtspAntrian` | `ptsp_antrians` | Data antrian PTSP detail |
| `PtspLoket` | `ptsp_lokels` | Loket PTSP |
| `PtspPenyerahanAc` | `ptsp_penyerahan_acs` | Penyerahan AC/Salinan |
| `SidangQueueTicket` | `sidang_queue_tickets` | Tiket antrian sidang |
| `QueueTicket` | `queue_tickets` | Tiket antrian generik (Pilar) |
| `QueueService` | `queue_services` | Katalog layanan antrian |
| `ServiceCounter` | `service_counters` | Loket/ruang sidang |
| `ServiceGroup` | `service_groups` | Grup layanan |

### Pendopo (Buku Tamu)
| Model | Tabel | Fungsi |
|---|---|---|
| `GuestbookEntry` | `guestbook_entries` | Entry buku tamu |
| `GuestbookSetting` | `guestbook_settings` | Konfigurasi buku tamu |

### Chat & CCTV
| Model | Tabel | Fungsi |
|---|---|---|
| `ChatMessage` | `messages` | Pesan chat internal |
| `ChatAlias` | `chat_aliases` | Alias display pengguna chat |
| `CctvCamera` | `cctv_cameras` | Konfigurasi & nama kamera CCTV |

### SIPP
| Model | Tabel | Fungsi |
|---|---|---|
| `SippCache` | `sipp_caches` | Cache data dari SIPP read-only |

### WA Caraka
| Model | Tabel | Fungsi |
|---|---|---|
| `WaCarakaConversation` | `wa_caraka_conversations` | Sesi obrolan WA |
| `WaCarakaConversationMark` | `wa_caraka_conversation_marks` | Penanda/label percakapan |
| `WaCarakaMessage` | `wa_caraka_messages` | Pesan WA masuk/keluar (+ media) |
| `WaCarakaTicket` | `wa_caraka_tickets` | Tiket pengaduan/konsultasi WA |
| `WaCarakaHandover` | `wa_caraka_handovers` | Handover antar operator |
| `WaCarakaMenu` | `wa_caraka_menus` | Menu chatbot WA |
| `WaCarakaSetting` | `wa_caraka_settings` | Konfigurasi WaCaraka |
| `WaCarakaSession` | `wa_caraka_sessions` | Sesi koneksi WA bridge |
| `WaCarakaSyncRun` | `wa_caraka_sync_runs` | Log sinkronisasi kontak |
| `WaCarakaDailyMetric` | `wa_caraka_daily_metrics` | Metrik harian WaCaraka |
| `WaCarakaMonthlySnapshot` | `wa_caraka_monthly_snapshots` | Snapshot bulanan WaCaraka |
| `WaCarakaLog` | `wa_caraka_logs` | Log aktivitas WaCaraka |
| `WaCarakaModel` | `wa_caraka_models` | Konfigurasi model AI WaCaraka |

### TDMS (Tool & Document Management System)
| Model | Tabel | Fungsi |
|---|---|---|
| `TdmsAsset` | `tdms_assets` | Aset/perangkat yang dikelola |
| `TdmsCategory` | `tdms_categories` | Kategori aset |
| `TdmsServiceRecord` | `tdms_service_records` | Catatan servis/perbaikan aset |
| `TdmsMaintenanceSchedule` | `tdms_maintenance_schedules` | Jadwal pemeliharaan berkala |
| `TdmsReplacement` | `tdms_replacements` | Catatan penggantian komponen |

### Lainnya
| Model | Tabel | Fungsi |
|---|---|---|
| `WidgetVisitor` | `widget_visitors` | Visitor widget publik |

---

## Konvensi Kode

### Backend (PHP/Laravel)
- **Controller:** PascalCase, suffix `Controller` → `PtspQueueController`
- **Model:** PascalCase, singular → `QueueTicket`
- **Service:** PascalCase, suffix `Service` → `WaCarakaService`, `SippService`
- **Trait:** PascalCase, prefix `Has` → `HasRolesAndPermissions`
- **Middleware:** PascalCase → `EnsureActiveUser`
- **Test:** PascalCase, suffix `Test` → `PtspQueueFlowTest`
- **Migration:** snake_case, format `YYYY_MM_DD_HHMMSS_description`

### Frontend (Vue/Inertia)
- **Page:** PascalCase → `PtspQueue.vue` di `resources/js/Pages/Lawangsewu/`
- **Sub-Page (modul besar):** di subfolder → `resources/js/Pages/Lawangsewu/WaCaraka/Index.vue`
- **Component:** PascalCase → `SimpleParticles.vue` di `resources/js/Components/`
- **Layout:** PascalCase, suffix `Layout` → `LawangsewuLayout.vue`

### Route Naming
- Format: `lawangsewu.[modul].[aksi]`
- Contoh: `lawangsewu.ptsp.index`, `lawangsewu.guestbook.store`
- Admin: `admin.[resource].[aksi]`

### Gaya Penulisan & Keterbacaan (Clean Code)
- **Analogi Sederhana:** Saat menjelaskan konsep, alur kerja, atau mendokumentasikan kode kepada User, AI WAJIB menyertakan analogi dunia nyata yang simpel agar mudah dipelajari.
- **Self-Documenting Code:** Penamaan variabel dan fungsi WAJIB sangat deskriptif dan sesuai dengan fungsinya. Tujuannya agar kode bisa menjelaskan dirinya sendiri tanpa perlu menambahkan komentar penjelas yang berlebihan.
- **Watermark Elegan:** Pada setiap akhir file atau script utama yang baru dibuat, tinggalkan jejak pembuat dengan gaya yang elegan, *cool*, dan simpel di baris paling bawah.
  - PHP/JS: `// developed by dbprakom™`
  - HTML/Vue: `<!-- developed by dbprakom™ -->`

---

## Testing Conventions

### PHP Tests
- Lokasi: `tests/Feature/` dan `tests/Unit/`
- Pattern: satu file test per fitur/flow
- Naming: `test_[deskripsi_behavior]` (snake_case)
- Wajib pakai `RefreshDatabase` trait

### Test Groups (Struktur Aktual)
```
tests/Feature/
├── Admin/
│   ├── CctvManagementTest.php
│   ├── FeaturePermissionUiAndAuditTest.php
│   ├── PendopoManagementTest.php
│   ├── SystemMonitorTest.php
│   └── UserAccessApprovalTest.php
├── Auth/
│   ├── AuthenticationTest.php
│   ├── EmailVerificationTest.php
│   ├── GoogleOAuthTest.php
│   ├── PasswordConfirmationTest.php
│   ├── PasswordResetTest.php
│   ├── PasswordUpdateTest.php
│   └── RegistrationTest.php
├── EdgeCases/
│   └── EdgeCaseTestingTest.php
├── ErrorHandling/
│   └── ErrorHandlingIntegrationTest.php
├── Modules/
│   ├── OmnichannelLiveChatTest.php
│   ├── SippHubTest.php
│   ├── WaCarakaAttachmentFlowTest.php
│   ├── WaCarakaModuleTest.php
│   └── WaCarakaWebhookTest.php
├── Portal/
│   ├── CctvApiTest.php
│   ├── ChatFlowTest.php
│   ├── DashboardFlowTest.php
│   ├── GuestbookFlowTest.php
│   ├── PakPpTest.php
│   ├── PelayananPtspTest.php
│   ├── PendopoSatelliteTest.php
│   ├── PilarModuleTest.php
│   ├── PortalApiContractTest.php
│   ├── PtspQueueFlowTest.php
│   ├── SidangQueueFlowTest.php
│   └── WidgetCompatSmokeTest.php
├── Tdms/
│   └── TdmsReadinessTest.php
├── Validation/
│   └── InputValidationIntegrationTest.php
├── LaporanControllerTest.php
├── ProfileTest.php
└── SystemHealthTest.php
```

### Baseline
- **~100+ tests** | 1 pre-existing fail (`ProfileTest` — bukan blocker)
- Target: **0 regresi** setiap kali ada perubahan
- Jalankan: `php artisan test` untuk verifikasi

---

## Services Utama

| Service | Tanggung Jawab |
|---|---|
| `WaCarakaService` | Core gateway WA: send, receive, media, sync kontak |
| `WaCarakaConversationService` | Manajemen percakapan & inbox operator |
| `WaCarakaChatbotService` | Logika chatbot & menu interaktif |
| `WaCarakaTicketService` | Lifecycle tiket pengaduan |
| `SippService` | Koneksi read-only ke DB SIPP (SIPP Hub) |
| `LegacyPendopoSyncService` | Sinkronisasi buku tamu dari legacy CI4 |
| `PilarQueueAuthority` | Otoritas antrian Pilar PASMG |
| `DocumentStylerService` | Styling & export dokumen Word (PakPp) |
| `PakPpDocxExportService` | Export PAK/PP ke format DOCX |
| `HealthCheckService` | Cek kesehatan semua service & dependency |
| `SystemMonitorService` | Monitoring sistem (CPU, RAM, disk, proses) |
| `VertexAiService` | Integrasi Google Vertex AI (AI features) |
| `TailscaleService` | Manajemen koneksi Tailscale (VPN mesh) |
| `OAuth2Service` | Manajemen OAuth2 token (Passport) |
| `GoogleIdTokenVerifier` | Verifikasi Google ID Token |
| `QueueMonitoringService` | Monitoring queue worker |
| `DatabaseOptimizationService` | Optimasi query & indeks DB |
| `DistributedTracingService` | Distributed tracing untuk debug |
| `ErrorTrackingService` | Pelacakan & pelaporan error |
| `PrometheusMetricsService` | Eksport metrik ke Prometheus |
| `RateLimitingService` | Rate limiting per IP/user |
| `Omnichannel/ChatOrchestrator` | Orkestrasi live chat omnichannel |

---

## Deployment Checklist

```bash
# 1. Install dependencies (jika ada perubahan)
composer install --no-dev --optimize-autoloader
npm install

# 2. Build frontend
npm run build

# 3. Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan event:clear

# 4. Run migrations
php artisan migrate --force

# 5. Seed permissions (jika ada perubahan)
php artisan db:seed --class=PermissionSeeder

# 6. Verify RBAC
php artisan rbac:verify

# 7. Run tests
php artisan test

# 8. Rebuild cache (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 9. Restart services
sudo supervisorctl restart lawangsewu-reverb    # WebSocket
sudo supervisorctl restart lawangsewu-queue     # Queue worker
sudo systemctl restart php8.3-fpm              # PHP-FPM

# 10. Verify
php artisan --version
curl -I https://lawangsewu.pa-semarang.go.id
```

---

## File Penting

| File | Fungsi |
|---|---|
| `bootstrap/app.php` | Middleware registration, CSRF config |
| `config/auth.php` | Super admin email, auth guards |
| `config/services.php` | Google OAuth credentials |
| `routes/web.php` | Semua route portal |
| `routes/auth.php` | Route autentikasi + OAuth |
| `routes/api.php` | Route API |
| `app/Support/LawangsewuPortal.php` | Helper portal utilities |
| `app/Core/Traits/HasRolesAndPermissions.php` | RBAC logic |
| `app/Http/Controllers/Auth/GoogleController.php` | SSO Google flow |
| `app/Services/WaCarakaService.php` | Core WA gateway (95KB — file besar) |
| `app/Services/SystemMonitorService.php` | System monitoring (19KB) |
| `app/Services/SippService.php` | Koneksi SIPP DB |

<!-- developed by dbprakom™ -->
