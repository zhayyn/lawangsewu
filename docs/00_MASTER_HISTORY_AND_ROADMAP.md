# 🏛️ Lawangsewu — Master History & Roadmap
> **Portal Digital Terpadu — Pengadilan Agama Semarang**  
> **URL Produksi:** https://lawangsewu.pa-semarang.go.id  
> **Dokumen ini:** Satu-satunya sumber kebenaran untuk histori dan arah pengembangan  
> **Terakhir diperbarui:** 2026-10-08 oleh `@monitor`

---

## 📌 Daftar Isi

1. [Tentang Lawangsewu](#tentang)
2. [Timeline History](#timeline)
3. [Sprint Log](#sprint-log)
4. [Status Modul Saat Ini](#status-modul)
5. [Roadmap ke Depan](#roadmap)
6. [Infrastruktur Server](#infrastruktur)
7. [Indeks Semua Dokumen](#indeks-dokumen)

---

## 1. Tentang Lawangsewu {#tentang}

**Lawangsewu V2** adalah portal digital internal Pengadilan Agama Semarang yang menyatukan:

- 💬 **WaCaraka** — Operator desk WhatsApp multi-operator, real-time
- 📋 **Antrian Digital** — PTSP & Sidang berbasis web
- 📖 **Buku Tamu Digital** — Form digital + notifikasi WA otomatis
- 📺 **Monitor CCTV** — Grid live stream semua kamera
- 📊 **SIPP Hub** — Agregasi data perkara dari SIPP (read-only)
- 💬 **Chat Internal** — Komunikasi antar pegawai via WebSocket
- 📄 **PakPp & TDMS** — Export dokumen + manajemen aset IT
- 🌐 **Widget Compat** — Widget embeddable untuk website publik

> **Bukan** website publik. Hanya dapat diakses pegawai terdaftar yang diaktifkan admin.

---

## 2. Timeline History {#timeline}

```
Apr 2026                                            Jun 2026    Okt 2026
   │                                                    │           │
   ▼                                                    ▼           ▼
───●────●────●────●───●───●──●──●──●──●──●──●──●──●──●─────────────●────▶
   │    │    │    │   │   │  │  │  │  │  │  │  │  │  │             │
   5    8    9   10  15  17 18 19 20 21 22 23 26 24 24-05        08-10
```

### 🟢 5 April 2026 — Fondasi & Sprint 1

> **Pencapaian:** Infrastructure, RBAC, Auth, Chat selesai

- ✅ Server dan struktur folder `/var/www/lawangsewu` siap
- ✅ Laravel 11 + PHP 8.3 + MariaDB terpasang
- ✅ RBAC System: 4 role (viewer, operator, admin, superadmin), 17 permission
- ✅ Migration: `permissions`, `role_permissions`, `users.is_superadmin`
- ✅ Laravel Passport OAuth2 (tabel dibuat, seeded)
- ✅ Modul Chat Internal selesai (Sprint 1)
- ⏳ Google SSO (Socialite) — belum
- 📊 **Status overall:** 28.5% (8/28 komponen)
- 📄 Dokumen: [01](./01_2026-04-05_lawangsewu-prd-blueprint.md) [02](./02_2026-04-05_phase-1-rbac-complete.md) [03](./03_2026-04-05_status-roadmap.txt) [04](./04_2026-04-05_google-auth-fix.md) [05](./05_2026-04-05_google-auth-test-cases.md) [06](./06_2026-04-05_sprint-1-status.md) [07](./07_2026-04-05_chat-completion-report.md) [08](./08_2026-04-05_audit-report.md)

---

### 🟢 8–10 April 2026 — Sprint 2 & Insiden Pertama

> **Pencapaian:** Sprint lanjut, insiden SSO Google, integrasi Pilar PASMG

- ✅ Tasks Sprint 2 dieksekusi
- 🔴 **Insiden:** Login SSO Google gagal — redirect URI mismatch
- ✅ Fixed: konfigurasi `GOOGLE_REDIRECT_URI` disesuaikan
- ✅ Integrasi Pilar Antrian PASMG ke Lawangsewu direncanakan
- ✅ Sprint 4 opening fixes
- 📄 Dokumen: [09](./09_2026-04-08_tasks-for-user.md) [10](./10_2026-04-09_laporan-insiden-sso-google.md) [11](./11_2026-04-09_pilar-antrian-pasmg-integrasi-ke-lawangsewu.md) [12](./12_2026-04-10_sprint-4-opening-fixes.md)

---

### 🟢 15 April 2026 — WaCaraka & SenopaTEA Framework

> **Pencapaian:** WaCaraka launch, SenopaTEA agent framework diperkenalkan

- ✅ Arsitektur SSO & RBAC difinalkan
- ✅ **SenopaTEA Framework** diperkenalkan — orkestrasi AI agent otonom
- ✅ WaCaraka: Status dashboard & kesiapan dikonfirmasi
- ✅ Modul WaCaraka terintegrasi ke Lawangsewu
- ✅ Kontrak API runtime Node.js WaCaraka ditetapkan
- 📡 WA Bridge endpoint: `http://192.168.88.33:8790`
- 📄 Dokumen: [13](./13_2026-04-15_arsitektur-sso-dan-rbac.md) [14](./14_2026-04-15_senopatea-framework-agen-otonom.md) [15](./15_2026-04-15_wa-caraka-status-dan-kesiapan-dashboard.md) [16](./16_2026-04-15_modul-wa-caraka-di-lawangsewu.md) [17](./17_2026-04-15_kontrak-runtime-node-wa-caraka.md)

---

### 🟢 17 April 2026 — Release Permission & Audit

> **Pencapaian:** Feature permission model & audit trail dirilis

- ✅ Feature Permission Model: role-level defaults + user-level overrides
- ✅ `feature_permissions` table + `permission_audit_logs` table
- ✅ Admin dashboard: live permission matrix, audit timeline
- ✅ Test suites: `UserAccessApprovalTest` ✅ `FeaturePermissionUiAndAuditTest` ✅
- 🏷️ **Tag Rilis:** `v2026.04.17-permissions-audit`
- 📄 Dokumen: [18](./18_2026-04-17_release-permission-dan-audit-lawangsewu.md)

---

### 🟢 18–19 April 2026 — Tailscale, OPcache, Buku Tamu, ERD

> **Pencapaian:** VPN Tailscale aktif, insiden OPcache ditangani, audit database lengkap

- ✅ **Tailscale VPN** diintegrasikan — server dapat diakses dari luar kantor
- ✅ IP Tailscale: `100.126.69.111`
- ✅ Runbook refresh runtime & deploy dibuat
- 🔴 **Insiden:** 404 route runtime karena OPcache tidak di-flush — fixed
- ✅ Audit konsolidasi buku tamu vs pendopo
- ✅ **Hotfix** WaCaraka outbound message berhasil
- ✅ Audit struktur database lengkap (semua tabel diaudit)
- ✅ **ERD Ringkas** database Lawangsewu dibuat
- ✅ Daftar cleanup teknis & hutang teknis diidentifikasi
- ✅ Audit WaCaraka end-to-end
- ✅ Laporan penjelasan integrasi eksternal
- 📄 Dokumen: [19](./19_2026-04-18_tailscale-subnet-router.md) [20](./20_2026-04-18_tailscale-integration-implementation-report.md) [21](./21_2026-04-18_runbook-refresh-runtime-deploy.md) [22](./22_2026-04-18_laporan-insiden-404-route-runtime-opcache.md) [23](./23_2026-04-18_audit-konsolidasi-buku-tamu-vs-pendopo.md) [24](./24_2026-04-19_laporan-hotfix-wa-caraka-outbound.md) [25](./25_2026-04-19_audit-struktur-database-modul-dan-integrasi-lawangsewu.md) [26](./26_2026-04-19_erd-ringkas-database-lawangsewu.md) [27](./27_2026-04-19_daftar-cleanup-teknis-dan-tindak-lanjut-lawangsewu.md) [28](./28_2026-04-19_audit-khusus-wa-caraka-end-to-end.md) [29](./29_2026-04-19_laporan-penjelasan-integrasi-eksternal-lawangsewu.md)

---

### 🟢 20–22 April 2026 — Modern Inbox, OOM Crisis, Reverb Safe Mode

> **Pencapaian:** WaCaraka modern inbox selesai; insiden OOM server — mode aman aktif

- ✅ WaCaraka Modern Inbox UI difinalkan
- ✅ Optimasi WaCaraka (response time, queue management)
- 🔴 **Insiden OOM (Out of Memory):** Server sempat hang karena memory pressure
- ✅ **earlyoom** dipasang sebagai kill-valve proaktif
- ✅ **Reverb Safe Mode** diaktifkan — WebSocket tetap stabil
- ✅ Audit login PTSP & dampak OOM dianalisis
- ✅ Server OOM & Reverb analysis report dibuat
- 📄 Dokumen: [30](./30_2026-04-20_wa-caraka-finalisasi-modern-inbox.md) [31](./31_2026-04-22_lawangsewu-stabilisasi-console-oom-dan-reverb.md) [32](./32_2026-04-22_penjelasan-reverb-dan-mode-aman-lawangsewu.md) [33](./33_2026-04-22_audit-login-ptsp-dan-dampak-oom.md) [35](./35_2026-04-20_wa_caraka_optimization_report.md) [39](./39_2026-04-22_server_oom_and_reverb_analysis.md)

---

### 🟢 21 April 2026 — Sprint 3: SIPP Hub ✅

> **Pencapaian:** SIPP Hub terintegrasi penuh — 55 tests ✅ semua pass

- ✅ SippHubController + routes (`/sipp-hub`, `/sipp-hub/refresh`)
- ✅ Cache sinkronisasi SIPP: 15 menit TTL
- ✅ Dashboard: metrik live, alert, sistem health SIPP Cache
- ✅ Developer standards & panduan ditetapkan
- ✅ Architecture & technical assessment dibuat
- 🧪 **Test Sprint 3:** 55 tests | 234 assertions | 0 regresi
- 📄 Dokumen: [36](./36_2026-04-21_sprint_3_sipp_hub_report.md) [37](./37_2026-04-21_architecture_and_technical_assessment.md) [38](./38_2026-04-21_developer_standards_and_guides.md)

---

### 🟢 23 April 2026 — Migrasi ke Server Baru

> **Pencapaian:** Server baru `satker-svr` aktif, migrasi via WinSCP + GitHub

- ✅ Panduan migrasi server baru via WinSCP & GitHub dibuat
- ✅ Server baru (`192.168.88.9`, hostname: `satker-svr`) aktif
- ✅ SSH key, GitHub remote, Tailscale terkonfigurasi
- 📄 Dokumen: [34](./34_2026-04-23_panduan-migrasi-server-baru-winscp-github.md) [40](./40_2026-04-23_panduan_migrasi_server.md)

---

### 🟢 26 April 2026 — Dokumentasi Besar (28 Dokumen Sekaligus)

> **Pencapaian:** Dokumentasi teknis lengkap & komprehensif dibuat massal

Ini adalah hari dengan output dokumentasi terbesar — 28 dokumen teknis ditulis dalam satu hari:

| Kategori | Dokumen |
|---|---|
| **API & Arsitektur** | API docs, Architecture overview, Blueprint |
| **Panduan Developer** | Config guide, Error handling, Feature flags, Input validation |
| **Database** | Database docs, Seeders guide |
| **Performance** | Optimization summary, Performance monitoring, Rate limiting |
| **Logging** | Logging guide, Request/response logging |
| **Keamanan** | Testing credentials security |
| **Testing** | Unit tests guide |
| **Operasional** | OOM prevention runbook, Quick reference card |
| **Sprint Reports** | Sprint 3 completion report, SIPP Hub integration |
| **Analisis** | Root cause analysis server hang, WaCaraka migration report, Technical assessment |

- 📄 Dokumen: [41](./41_2026-04-26_api.md) hingga [68](./68_2026-04-26_wa_caraka_migration_report.md)

---

### 🟢 24 Mei 2026 — Daily Report & Review

> **Pencapaian:** Daily report rutin mulai dibuat

- ✅ Format daily report ditetapkan
- ✅ Review kondisi sistem dan queue
- 📄 Dokumen: [2026-05-24_daily-report.md](./2026-05-24_daily-report.md)

---

### 🟢 30–31 Mei 2026 — CCTV Relay & Network Monitor

> **Pencapaian:** Infrastruktur CCTV diperkuat dengan relay dan monitoring jaringan

- ✅ CCTV relay integration (go2rtc / MediaMTX)
- ✅ Network monitor setup
- ✅ Reverb + Cloudflare optimasi (WebSocket via tunnel)
- 📄 Dokumen: [cctv-relay-integration.md](./cctv-relay-integration.md) [network-monitor.md](./network-monitor.md) [reverb-cloudflare.md](./reverb-cloudflare.md)

---

### 🟢 2–5 Juni 2026 — TV Media Feature

> **Pencapaian:** Fitur TV Media dirilis — tampilan antrian ke layar TV

- ✅ Fitur TV Media: tampilan antrian sidang & PTSP di layar TV kantor
- ✅ Integrasi Canva embed (diganti embed standar karena 403 Forbidden)
- ✅ Sinkronisasi pegawai SIKEP ke tampilan TV Media
- 📄 Dokumen: [2026-06-03_tvmedia-feature.md](./2026-06-03_tvmedia-feature.md) [tvmedia.md](./tvmedia.md)

---

### 🟢 9 Juni 2026 — Guestbook × WA Integration (Commit Terakhir)

> **Pencapaian:** Buku tamu dengan nomor HP wajib + notifikasi WA otomatis

- ✅ Kolom `phone` (VARCHAR 20) ditambahkan ke `guestbook_entries`
- ✅ Nomor HP wajib diisi sebelum kamera aktif (validasi JS)
- ✅ `WaCarakaService::queueText()` dipanggil async setelah submit buku tamu
- ✅ Mock test pada `GuestbookFlowTest` — tidak perlu queue real saat CI
- 🏷️ **Commit HEAD:** `f93bb22` — *feat: add mandatory phone number & automated WA feedback notification for guestbook*
- 📄 Dokumen: [2026-06-09_guestbook-wa-integration.md](./2026-06-09_guestbook-wa-integration.md)

---

### 🟡 Juni–Oktober 2026 — Pengembangan Aktif (Belum di-Commit)

> **Status:** Kode berjalan di server tapi **belum di-push ke GitHub** ⚠️

Berdasarkan `git status`, ada pengembangan besar yang sedang berjalan:

- 🔄 **WaCaraka Personal** — personal WA runtime per user (`wa-runtime-personal/`)
- 🔄 **Pasemarang Sync Monitor** — dashboard sinkronisasi antar sistem
- 🔄 **WaCarakaPersonalAdminController** + **WaCarakaPersonalController**
- 🔄 Migration: `2026_06_17_192216_add_source_to_wa_caraka_tables`
- 🔄 Vue pages: `WaCarakaPersonal/`, `WaCarakaPersonalManager.vue`, `PasemarangSyncMonitor.vue`

---

## 3. Sprint Log {#sprint-log}

| Sprint | Periode | Fokus | Status |
|---|---|---|---|
| **Sprint 0** | < Apr 2026 | Setup server, Laravel, infrastruktur dasar | ✅ Done |
| **Sprint 1** | 2026-04-05 | RBAC + Auth + Chat + SSO Google | ✅ Done |
| **Sprint 2** | 2026-04-08~10 | Bug fixes, Pilar PASMG, SSO fix | ✅ Done |
| **Sprint 3** | 2026-04-15~21 | WaCaraka + SIPP Hub + Tailscale | ✅ Done (55 tests ✅) |
| **Sprint 4** | 2026-04-22~26 | OOM fixes, Reverb safe mode, Migrasi server, Dokumentasi massal | ✅ Done |
| **Sprint 5** | 2026-05-24~Jun | CCTV Relay, TV Media, Guestbook WA | ✅ Done |
| **Sprint 6** | Jun–sekarang | WaCaraka Personal, Pasemarang Sync | 🔄 In Progress (belum push) |

---

## 4. Status Modul Saat Ini {#status-modul}

| Modul | Status | Keterangan |
|---|---|---|
| 🏠 **Dashboard** | ✅ Production | Live stats, metrics, alerts |
| 📋 **PTSP Queue** | ✅ Production | Antrian digital berjalan |
| ⚖️ **Sidang Queue** | ✅ Production | Antrian persidangan berjalan |
| 📖 **Pendopo (Buku Tamu)** | ✅ Production | + WA notif otomatis (Jun 2026) |
| 💬 **Chat Internal** | ✅ Production | Via Reverb WebSocket |
| 📺 **CCTV Monitor** | ✅ Production | Grid live stream + relay |
| 📊 **SIPP Hub** | ✅ Production | Cache 15 menit, read-only |
| 📱 **WaCaraka** | ✅ Production | Multi-operator, modern inbox |
| 📺 **TV Media** | ✅ Production | Antrian di layar TV kantor |
| 🔗 **Pilar PASMG** | ✅ Production | Hub antrian terpadu |
| 📄 **PakPp** | ✅ Production | Export DOCX |
| 📱 **WaCaraka Personal** | 🔄 In Dev | Belum di-push ke GitHub |
| 📊 **Pasemarang Sync Monitor** | 🔄 In Dev | Belum di-push ke GitHub |
| 🤖 **Ollama / AI** | ✅ Running | Port 11434 aktif di server |
| 🖥️ **VM Windows 7** | ✅ Running | KVM/QEMU, SPICE port 5900 |

---

## 5. Roadmap ke Depan {#roadmap}

> Rekomendasi berdasarkan analisis kondisi server dan codebase per 2026-10-08

### 🔴 Prioritas Mendesak (Segera)

| # | Item | Alasan |
|---|---|---|
| 1 | **Commit & push Sprint 6** (WaCaraka Personal, Pasemarang Sync) | 4+ bulan kode tidak di-backup ke GitHub — risiko kehilangan |
| 2 | **Tutup port MariaDB 3306** ke `0.0.0.0` | Security — DB tidak perlu exposed ke seluruh LAN |
| 3 | **Cek suhu CPU** (saat ini 84°C) | Mendekati TjMax Xeon E-2224 — perlu cek cooling fisik |
| 4 | **Turunkan load average** (saat ini 8.83 pada 4 core) | CPU overload — identifikasi bottleneck |

### 🟡 Prioritas Menengah (1–2 Bulan)

| # | Item | Rekomendasi |
|---|---|---|
| 5 | **Index tabel `widget_visitors`** | Slow query INSERT 100–220ms terjadi setiap hari, perlu index |
| 6 | **Finalisasi WaCaraka Personal** | Fitur hampir siap, push dan deploy ke production |
| 7 | **Dokumentasi Sprint 6** | Buat `docs/YYYY-MM-DD_wacaraka-personal.md` dan `docs/YYYY-MM-DD_pasemarang-sync.md` |
| 8 | **Evaluasi VM Windows 7** | Sudah 193+ jam CPU — pertimbangkan upgrade ke Win10 atau tutup jika tidak terpakai |
| 9 | **Install lm-sensors** | Untuk monitoring suhu yang lebih detail |

### 🟢 Roadmap Fitur (Jangka Menengah-Panjang)

| # | Fitur | Deskripsi | Prioritas |
|---|---|---|---|
| 10 | **Broadcast WA** | Kirim pesan ke banyak nomor sekaligus | P1 |
| 11 | **Laporan Harian WA (PDF)** | Export PDF statistik WaCaraka harian/mingguan | P1 |
| 12 | **WaCaraka Chatbot** | Auto-reply terprogram, menu interaktif | P2 |
| 13 | **Dashboard Tren WA** | Grafik Chart.js untuk tren pesan/waktu | P2 |
| 14 | **Prometheus Metrics** | Eksport metrik ke Prometheus/Grafana | P2 |
| 15 | **Omnichannel LiveChat** | Live chat terpadu (service sudah ada di codebase) | P2 |
| 16 | **Upgrade MySQL → 8.x** | Context.md menyebut MySQL 8.x tapi saat ini MariaDB | P3 |
| 17 | **Distributed Tracing** | Debug end-to-end dengan trace ID | P3 |

---

## 6. Infrastruktur Server {#infrastruktur}

> Snapshot per 2026-10-08

```
┌─────────────────────────────────────────────────────┐
│  satker-svr  (192.168.88.9)                         │
│  Intel Xeon E-2224 @ 3.4GHz | 4 Core | 31 GB RAM   │
│  Ubuntu 24.04.5 LTS | Uptime: 34 hari               │
│                                                      │
│  Storage:                                            │
│  ├── /dev/md0 RAID1 (sda+sdb) 899GB @ 58%  ✅       │
│  ├── /dev/sdc 1.8TB @ 27%                           │
│  └── SMB: BANK_DATA_PASMG 457GB @ 50%               │
│                                                      │
│  Network:                                            │
│  ├── LAN: br0 192.168.88.9                          │
│  ├── VPN: Tailscale 100.126.69.111                  │
│  └── VPN: WireGuard 10.19.9.1/24                    │
│                                                      │
│  Services Aktif:                                     │
│  Apache · PHP-FPM 8.3 · MariaDB 10.11               │
│  Redis · Reverb(8080) · Ollama · Docker             │
│  ClamAV · Fail2Ban · Samba · KVM                    │
│                                                      │
│  VM: Windows 7 (KVM) — 2vCPU, 4GB RAM              │
└─────────────────────────────────────────────────────┘
         │                        │
         ▼                        ▼
[192.168.88.33]           [192.168.88.201]
WA Bridge Node.js         NAS BANK_DATA_PASMG
Port 8790 (Baileys)       SMB Share
```

---

## 7. Indeks Semua Dokumen {#indeks-dokumen}

### 📂 Referensi Tetap (Selalu Update)

| File | Isi |
|---|---|
| [00_MASTER_HISTORY_AND_ROADMAP.md](./00_MASTER_HISTORY_AND_ROADMAP.md) | **File ini** — History & Roadmap master |
| [README.md](./README.md) | Index utama + quick reference |
| [01_OVERVIEW_SISTEM.md](./01_OVERVIEW_SISTEM.md) | Overview sistem, tech stack, infrastruktur |
| [02_AUDIT_MODUL_DAN_FITUR.md](./02_AUDIT_MODUL_DAN_FITUR.md) | Status semua modul (update berkala) |
| [03_PRD_PRODUCT_REQUIREMENTS.md](./03_PRD_PRODUCT_REQUIREMENTS.md) | PRD — goals, user persona, feature set |
| [04_BLUEPRINT_ARSITEKTUR.md](./04_BLUEPRINT_ARSITEKTUR.md) | Blueprint teknis & DB schema |
| [05_WACARAKA_TEKNIS.md](./05_WACARAKA_TEKNIS.md) | Dokumentasi teknis modul WaCaraka |

### 📅 Log Harian & Sprint (Kronologis)

| Tanggal | File | Topik |
|---|---|---|
| 2026-04-05 | [01_2026-04-05_lawangsewu-prd-blueprint.md](./01_2026-04-05_lawangsewu-prd-blueprint.md) | PRD & Blueprint awal |
| 2026-04-05 | [02_2026-04-05_phase-1-rbac-complete.md](./02_2026-04-05_phase-1-rbac-complete.md) | RBAC Phase 1 selesai |
| 2026-04-05 | [03_2026-04-05_status-roadmap.txt](./03_2026-04-05_status-roadmap.txt) | Status & progress 28.5% |
| 2026-04-05 | [04_2026-04-05_google-auth-fix.md](./04_2026-04-05_google-auth-fix.md) | Fix Google Auth |
| 2026-04-05 | [05_2026-04-05_google-auth-test-cases.md](./05_2026-04-05_google-auth-test-cases.md) | Test cases Google Auth |
| 2026-04-05 | [06_2026-04-05_sprint-1-status.md](./06_2026-04-05_sprint-1-status.md) | Status Sprint 1 |
| 2026-04-05 | [07_2026-04-05_chat-completion-report.md](./07_2026-04-05_chat-completion-report.md) | Chat selesai |
| 2026-04-05 | [08_2026-04-05_audit-report.md](./08_2026-04-05_audit-report.md) | Audit report awal |
| 2026-04-08 | [09_2026-04-08_tasks-for-user.md](./09_2026-04-08_tasks-for-user.md) | Task items |
| 2026-04-09 | [10_2026-04-09_laporan-insiden-sso-google.md](./10_2026-04-09_laporan-insiden-sso-google.md) | 🔴 Insiden SSO |
| 2026-04-09 | [11_2026-04-09_pilar-antrian-pasmg-integrasi-ke-lawangsewu.md](./11_2026-04-09_pilar-antrian-pasmg-integrasi-ke-lawangsewu.md) | Pilar PASMG |
| 2026-04-10 | [12_2026-04-10_sprint-4-opening-fixes.md](./12_2026-04-10_sprint-4-opening-fixes.md) | Sprint 4 fixes |
| 2026-04-15 | [13_2026-04-15_arsitektur-sso-dan-rbac.md](./13_2026-04-15_arsitektur-sso-dan-rbac.md) | Arsitektur SSO & RBAC |
| 2026-04-15 | [14_2026-04-15_senopatea-framework-agen-otonom.md](./14_2026-04-15_senopatea-framework-agen-otonom.md) | SenopaTEA |
| 2026-04-15 | [15_2026-04-15_wa-caraka-status-dan-kesiapan-dashboard.md](./15_2026-04-15_wa-caraka-status-dan-kesiapan-dashboard.md) | WaCaraka status |
| 2026-04-15 | [16_2026-04-15_modul-wa-caraka-di-lawangsewu.md](./16_2026-04-15_modul-wa-caraka-di-lawangsewu.md) | Modul WaCaraka |
| 2026-04-15 | [17_2026-04-15_kontrak-runtime-node-wa-caraka.md](./17_2026-04-15_kontrak-runtime-node-wa-caraka.md) | Kontrak runtime |
| 2026-04-17 | [18_2026-04-17_release-permission-dan-audit-lawangsewu.md](./18_2026-04-17_release-permission-dan-audit-lawangsewu.md) | 🏷️ Release permissions |
| 2026-04-18 | [19_2026-04-18_tailscale-subnet-router.md](./19_2026-04-18_tailscale-subnet-router.md) | Tailscale VPN |
| 2026-04-18 | [20_2026-04-18_tailscale-integration-implementation-report.md](./20_2026-04-18_tailscale-integration-implementation-report.md) | Laporan Tailscale |
| 2026-04-18 | [21_2026-04-18_runbook-refresh-runtime-deploy.md](./21_2026-04-18_runbook-refresh-runtime-deploy.md) | Runbook deploy |
| 2026-04-18 | [22_2026-04-18_laporan-insiden-404-route-runtime-opcache.md](./22_2026-04-18_laporan-insiden-404-route-runtime-opcache.md) | 🔴 Insiden OPcache |
| 2026-04-18 | [23_2026-04-18_audit-konsolidasi-buku-tamu-vs-pendopo.md](./23_2026-04-18_audit-konsolidasi-buku-tamu-vs-pendopo.md) | Audit buku tamu |
| 2026-04-19 | [24_2026-04-19_laporan-hotfix-wa-caraka-outbound.md](./24_2026-04-19_laporan-hotfix-wa-caraka-outbound.md) | Hotfix WaCaraka |
| 2026-04-19 | [25_2026-04-19_audit-struktur-database-modul-dan-integrasi-lawangsewu.md](./25_2026-04-19_audit-struktur-database-modul-dan-integrasi-lawangsewu.md) | Audit DB |
| 2026-04-19 | [26_2026-04-19_erd-ringkas-database-lawangsewu.md](./26_2026-04-19_erd-ringkas-database-lawangsewu.md) | ERD Database |
| 2026-04-19 | [27_2026-04-19_daftar-cleanup-teknis-dan-tindak-lanjut-lawangsewu.md](./27_2026-04-19_daftar-cleanup-teknis-dan-tindak-lanjut-lawangsewu.md) | Hutang teknis |
| 2026-04-19 | [28_2026-04-19_audit-khusus-wa-caraka-end-to-end.md](./28_2026-04-19_audit-khusus-wa-caraka-end-to-end.md) | Audit WaCaraka E2E |
| 2026-04-19 | [29_2026-04-19_laporan-penjelasan-integrasi-eksternal-lawangsewu.md](./29_2026-04-19_laporan-penjelasan-integrasi-eksternal-lawangsewu.md) | Integrasi eksternal |
| 2026-04-20 | [30_2026-04-20_wa-caraka-finalisasi-modern-inbox.md](./30_2026-04-20_wa-caraka-finalisasi-modern-inbox.md) | Modern inbox final |
| 2026-04-20 | [35_2026-04-20_wa_caraka_optimization_report.md](./35_2026-04-20_wa_caraka_optimization_report.md) | Optimasi WaCaraka |
| 2026-04-21 | [36_2026-04-21_sprint_3_sipp_hub_report.md](./36_2026-04-21_sprint_3_sipp_hub_report.md) | Sprint 3 SIPP Hub |
| 2026-04-21 | [37_2026-04-21_architecture_and_technical_assessment.md](./37_2026-04-21_architecture_and_technical_assessment.md) | Assessment arsitektur |
| 2026-04-21 | [38_2026-04-21_developer_standards_and_guides.md](./38_2026-04-21_developer_standards_and_guides.md) | Standar developer |
| 2026-04-22 | [31_2026-04-22_lawangsewu-stabilisasi-console-oom-dan-reverb.md](./31_2026-04-22_lawangsewu-stabilisasi-console-oom-dan-reverb.md) | 🔴 OOM + Reverb |
| 2026-04-22 | [32_2026-04-22_penjelasan-reverb-dan-mode-aman-lawangsewu.md](./32_2026-04-22_penjelasan-reverb-dan-mode-aman-lawangsewu.md) | Reverb safe mode |
| 2026-04-22 | [33_2026-04-22_audit-login-ptsp-dan-dampak-oom.md](./33_2026-04-22_audit-login-ptsp-dan-dampak-oom.md) | Audit PTSP + OOM |
| 2026-04-22 | [39_2026-04-22_server_oom_and_reverb_analysis.md](./39_2026-04-22_server_oom_and_reverb_analysis.md) | Analisis OOM |
| 2026-04-23 | [34_2026-04-23_panduan-migrasi-server-baru-winscp-github.md](./34_2026-04-23_panduan-migrasi-server-baru-winscp-github.md) | Migrasi server |
| 2026-04-23 | [40_2026-04-23_panduan_migrasi_server.md](./40_2026-04-23_panduan_migrasi_server.md) | Panduan migrasi |
| 2026-04-26 | [41](./41_2026-04-26_api.md)–[68](./68_2026-04-26_wa_caraka_migration_report.md) | 📚 28 dokumen teknis lengkap |
| 2026-05-24 | [2026-05-24_daily-report.md](./2026-05-24_daily-report.md) | Daily report |
| 2026-05-31 | [cctv-relay-integration.md](./cctv-relay-integration.md) | CCTV relay |
| 2026-05-31 | [network-monitor.md](./network-monitor.md) | Network monitoring |
| 2026-05-31 | [reverb-cloudflare.md](./reverb-cloudflare.md) | Reverb + Cloudflare |
| 2026-06-02 | [mediamtx-nvr1-corrected.yml](./mediamtx-nvr1-corrected.yml) | Config MediaMTX |
| 2026-06-03 | [2026-06-03_tvmedia-feature.md](./2026-06-03_tvmedia-feature.md) | TV Media feature |
| 2026-06-05 | [tvmedia.md](./tvmedia.md) | TV Media docs |
| 2026-06-09 | [2026-06-09_guestbook-wa-integration.md](./2026-06-09_guestbook-wa-integration.md) | Guestbook + WA |

### 📂 Panduan Teknis Referensi

| File | Isi |
|---|---|
| [05_WACARAKA_TEKNIS.md](./05_WACARAKA_TEKNIS.md) | WaCaraka teknis lengkap |
| [06_PANDUAN_SERVER33_WA_BRIDGE.md](./06_PANDUAN_SERVER33_WA_BRIDGE.md) | Setup WA Bridge server 33 |
| [07_LAPORAN_AUDIT_SSO.md](./07_LAPORAN_AUDIT_SSO.md) | Audit SSO |
| [08_INSIDEN_SSO_INCOGNITO_2026-05-03.md](./08_INSIDEN_SSO_INCOGNITO_2026-05-03.md) | Post-mortem SSO incognito |
| [41_2026-04-26_api.md](./41_2026-04-26_api.md) | Dokumentasi API |
| [44_2026-04-26_configuration_guide.md](./44_2026-04-26_configuration_guide.md) | Panduan konfigurasi |
| [56_2026-04-26_oom_prevention_runbook.md](./56_2026-04-26_oom_prevention_runbook.md) | Runbook pencegahan OOM |
| [59_2026-04-26_quick_reference.md](./59_2026-04-26_quick_reference.md) | Quick reference card |
| [67_2026-04-26_unit_tests_guide.md](./67_2026-04-26_unit_tests_guide.md) | Panduan unit tests |

---

## 📏 Konvensi Penambahan Dokumen Baru

```
docs/YYYY-MM-DD_judul-singkat.md         ← log harian / insiden / fitur
docs/NN_YYYY-MM-DD_judul-singkat.md      ← dokumen bernomor urut
```

**Aturan update:**
- Setiap fitur baru → update `02_AUDIT_MODUL_DAN_FITUR.md`
- Perubahan arsitektur → update `04_BLUEPRINT_ARSITEKTUR.md`
- Insiden produksi → buat dokumen baru `YYYY-MM-DD_insiden-[nama].md`
- Sprint selesai → buat `YYYY-MM-DD_sprint-N-completion.md`
- **Update file ini** setiap ada milestone penting

---

*Dibuat oleh `@monitor` (Antigravity Server Expert Agent) — 2026-10-08*  
*<!-- developed by dbprakom™ -->*
