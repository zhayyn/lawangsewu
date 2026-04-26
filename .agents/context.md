# 🏛️ Konteks Arsitektur Lawangsewu

> Dokumen ini adalah sumber kebenaran untuk semua agent SenopaTEA.
> Setiap agent WAJIB membaca file ini sebelum melakukan aksi apapun.

---

## Identitas Sistem

| Atribut | Nilai |
|---|---|
| **Nama** | Lawangsewu |
| **Organisasi** | Pengadilan Agama Semarang |
| **Deskripsi** | Portal digital terpadu: antrian, buku tamu, chat, CCTV, monitoring |
| **Superadmin** | `dbprakom@gmail.com` |
| **Domain Production** | `lawangsewu.pa-semarang.go.id` |

---

## Tech Stack (LOCKED — Tidak Boleh Diubah)

| Layer | Teknologi | Versi |
|---|---|---|
| **Framework** | Laravel | 13.x |
| **PHP** | PHP | 8.3+ |
| **Frontend** | Vue 3 + Inertia.js 2 | ^3.4 / ^2.0 |
| **Styling** | Tailwind CSS | ^3.2 |
| **Auth** | Google OAuth via Socialite | ^5.26 |
| **Token/API** | Laravel Sanctum | ^4.0 |
| **Realtime** | Laravel Reverb + Echo | ^2.0 |
| **Database** | MySQL | 8.x |
| **Test PHP** | PHPUnit | ^12.0 |
| **Test JS** | Vitest | ^2.1 |
| **Build** | Vite | ^6.0 |
| **Routing JS** | Ziggy | ^2.0 |

---

## Arsitektur Modul

```
Lawangsewu
├── 🏠 Dashboard         - Ringkasan seluruh modul
├── 📋 PTSP Queue        - Antrian Pelayanan Terpadu Satu Pintu
├── ⚖️ Sidang Queue      - Antrian Persidangan
├── 📖 Pendopo           - Buku Tamu Digital (migrasi dari CI4)
├── 💬 Chat              - Komunikasi Internal (Reverb WebSocket)
├── 📺 CCTV              - Monitoring Kamera
├── 📊 Pilar PASMG       - Hub Pilar Antrian Terpadu
├── 📡 SIPP Hub          - Integrasi data SIPP
├── 💬 WA Caraka         - Gateway WhatsApp Internal & Inbox Operator
├── 🔧 Admin Panel       - Kelola user, kamera, pendopo, allowlist
└── 🌐 Widget Compat     - Widget publik legacy (embed, RSS, dll)
```

---

## Autentikasi & Otorisasi

### SSO (Single Sign-On)
- Login **hanya via Google OAuth** (Socialite)
- Email harus terdaftar di `google_access_allowlist` atau match `SUPERADMIN_EMAIL`
- User baru → status `is_active = false` → perlu approval admin
- Superadmin auto-activate, auto-role `admin`

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

| Model | Tabel | Fungsi |
|---|---|---|
| `User` | `users` | User dengan role, is_active, is_superadmin, google_id |
| `GoogleAccessAllowlist` | `google_access_allowlist` | Email whitelist untuk login |
| `Permission` | `permissions` | Definisi permission (name, module) |
| `RolePermission` | `role_permissions` | Mapping role → permission |
| `PtspQueueTicket` | `ptsp_queue_tickets` | Tiket antrian PTSP |
| `SidangQueueTicket` | `sidang_queue_tickets` | Tiket antrian sidang |
| `QueueTicket` | `queue_tickets` | Tiket antrian generik (Pilar) |
| `QueueService` | `queue_services` | Katalog layanan antrian |
| `ServiceCounter` | `service_counters` | Loket/ruang sidang |
| `ServiceGroup` | `service_groups` | Grup layanan |
| `GuestbookEntry` | `guestbook_entries` | Entry buku tamu |
| `GuestbookSetting` | `guestbook_settings` | Konfigurasi buku tamu |
| `ChatMessage` | `messages` | Pesan chat |
| `ChatAlias` | `chat_aliases` | Alias display pengguna chat |
| `CctvCamera` | `cctv_cameras` | Konfigurasi kamera CCTV |
| `SippCache` | `sipp_caches` | Cache data dari SIPP |
| `WaCarakaConversation` | `wa_caraka_conversations` | Sesi obrolan WA |
| `WaCarakaMessage` | `wa_caraka_messages` | Pesan WA masuk/keluar |
| `WaCarakaTicket` | `wa_caraka_tickets` | Tiket pengaduan/konsultasi WA |

---

## Konvensi Kode

### Backend (PHP/Laravel)
- **Controller:** PascalCase, suffix `Controller` → `PtspQueueController`
- **Model:** PascalCase, singular → `QueueTicket`
- **Service:** PascalCase, suffix `Service` → `LegacyPendopoSyncService`
- **Trait:** PascalCase, prefix `Has` → `HasRolesAndPermissions`
- **Middleware:** PascalCase → `EnsureActiveUser`
- **Test:** PascalCase, suffix `Test` → `PtspQueueFlowTest`
- **Migration:** snake_case, format `YYYY_MM_DD_HHMMSS_description`

### Frontend (Vue/Inertia)
- **Page:** PascalCase → `PtspQueue.vue` di `resources/js/Pages/Lawangsewu/`
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

### Test Groups
```
tests/Feature/
├── Admin/          # Fitur admin panel
├── Auth/           # Alur autentikasi + OAuth
└── Portal/         # Fitur portal utama (dashboard, chat, queue, dll)
```

### Baseline
- **74 test pass** | 1 pre-existing fail (ProfileTest — bukan blocker)
- Target: **0 regresi** setiap kali ada perubahan

---

## Deployment Checklist

```bash
# 1. Build frontend
npm run build

# 2. Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 3. Run migrations
php artisan migrate --force

# 4. Seed permissions (jika ada perubahan)
php artisan db:seed --class=PermissionSeeder

# 5. Verify RBAC
php artisan rbac:verify

# 6. Run tests
php artisan test

# 7. Restart services
php artisan reverb:restart  # WebSocket
php artisan queue:restart   # Queue worker

# 8. Verify
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
