# 01 — Overview Sistem Lawangsewu V2

> **Status:** ✅ Production  
> **URL:** https://lawangsewu.pa-semarang.go.id  
> **Server:** VPS Pengadilan Agama Semarang  
> **Last Updated:** 2026-05-08  

---

## Apa itu Lawangsewu?

**Lawangsewu** adalah portal digital terpadu internal Pengadilan Agama Semarang. Sistem ini berfungsi sebagai:

1. **Dashboard operasional** bagi pegawai dan operator
2. **Hub komunikasi** melalui WhatsApp (WaCaraka), Chat Internal, dan Interkom
3. **Monitor layanan publik** — antrian PTSP, antrian sidang, jadwal persidangan
4. **Sistem manajemen aset** (TDMS) dan dokumen
5. **Widget embed** untuk website publik PA Semarang

Sistem ini **bukan** website publik — hanya dapat diakses oleh pegawai yang telah terdaftar dan diaktifkan oleh admin.

---

## Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend | Vue 3 (Inertia.js, Vite) |
| Styling | Tailwind CSS |
| Database | MySQL (driver: `mysql`) |
| Cache | File cache (driver: `file`) |
| Queue | Database queue (driver: `database`) |
| WebSocket (Realtime) | Laravel Reverb (port 8080) |
| WhatsApp Bridge | Node.js / Baileys (Server 33, port 8790) |
| Auth | Session auth + Google SSO (OAuth2) |
| HTTP Client | Guzzle / Laravel HTTP Facade |
| Asset Build | Vite 6 |

---

## Infrastruktur Runtime

```
[Pegawai Browser]
      │
      ▼
[Nginx → PHP-FPM 8.3]      ← lawangsewu.pa-semarang.go.id
      │
      ├─ [Laravel App] ──── MySQL DB
      │         │
      │         ├─ Queue Worker (database)  ← php artisan queue:work
      │         └─ Reverb WebSocket Server  ← php artisan reverb:start (port 8080)
      │
      └─ [WA Bridge HTTP] ─── http://192.168.88.33:8790  (Server 33 LAN)
                                    │
                                    └─ WhatsApp via Baileys
```

### Proses yang Berjalan (Saat Audit)
| Proses | Status |
|---|---|
| `php-fpm` master + 4 workers | ✅ Running |
| `php artisan reverb:start --host=0.0.0.0 --port=8080` | ✅ Running |
| `php artisan queue:work database --queue=default` | ✅ Running |
| WA Bridge di `192.168.88.33:8790` | ✅ Merespons (auth check OK) |
| Supervisor | ⚠️ Tidak terdeteksi (worker mungkin dikelola manual/cron) |

---

## Statistik Database (Per 2026-05-08)

| Tabel | Record |
|---|---|
| `users` | 14 |
| `wa_caraka_messages` | 1.443 |
| `wa_caraka_conversations` | 163 |
| `wa_caraka_tickets` | 0 |
| `wa_caraka_logs` | 626 |
| `chat_messages` | 0 |
| `guestbook_entries` | 440 |
| `queue_tickets` | 1 |
| `sidang_queue_tickets` | 0 |
| `ptsp_queue_tickets` | 1 |
| `tdms_assets` | 0 |
| `sipp_caches` | 3 |
| `cctv_cameras` | 19 |
| `oauth_clients` | 1 |
| `feature_permissions` | 44 |
| `wa_caraka_daily_metrics` | 14 |

---

## Role Pengguna

| Role | Akses |
|---|---|
| `viewer` | Read-only — dashboard & laporan |
| `operator` | Operator aktif — WaCaraka, antrian, TDMS |
| `useradmin` | Manajemen user terbatas |
| `admin` | Semua fitur + admin panel |
| `superadmin` | Full access termasuk tools destruktif |

Auth guard: `auth`, `verified`, `active`, `role:xxx`

---

## Folder Utama

```
lawangsewu/
├── app/
│   ├── Http/Controllers/        ← 17 controller utama + 3 subdirektori (Admin, Api, Auth)
│   ├── Models/                  ← 37 model Eloquent
│   ├── Services/                ← 19 service class
│   ├── Jobs/                    ← Queue jobs (WA outbound, dll)
│   ├── Events/ & Listeners/     ← Realtime events (Reverb)
│   └── Support/                 ← Helper classes (LawangsewuPortal, WaCarakaDatabase)
├── resources/js/Pages/
│   ├── Admin/                   ← 8 halaman admin
│   ├── Lawangsewu/              ← 7 halaman + 3 submodul
│   ├── Auth/                    ← Login, register
│   └── Profile/                 ← Profil user
├── widgets/                     ← Widget PHP standalone (embed ke website publik)
├── docs/                        ← Dokumentasi (folder ini)
└── .agents/                     ← SenopaTEA agent framework
```
