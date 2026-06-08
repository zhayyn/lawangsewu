# 02 — Audit Modul & Status Fitur

> **Tanggal Audit:** 2026-05-08  
> **Auditor:** SenopaTEA @qa  

---

## Legenda Status

| Simbol | Arti |
|---|---|
| ✅ | Running & berfungsi normal |
| ⚠️ | Ada catatan / kondisi tertentu |
| ❌ | Tidak berfungsi / belum diimplementasi |
| 🔧 | Fitur ada, perlu konfigurasi tambahan |

---

## Modul 1 — Authentication & Access Control

**Route prefix:** `/` (root, login, register)  
**Controller:** `Auth/*`, `ProfileController`  
**Status:** ✅ Running

| Fitur | Status | Catatan |
|---|---|---|
| Login email + password | ✅ | Session-based |
| Google SSO (OAuth2) | ✅ | 1 OAuth client terdaftar di DB |
| Google SSO Allowlist | ✅ | `google_access_allowlist` table |
| Email verification | ✅ | `verified` middleware |
| User activation (`active` middleware) | ✅ | Admin harus aktifkan akun baru |
| Role-based access control | ✅ | `role:` middleware, 5 role |
| Feature permissions (granular) | ✅ | 44 permission entries di DB |
| Login history tracking | ✅ | `login_histories` table |
| Profile management | ✅ | `ProfileController` |
| Password reset | ✅ | Standard Laravel |

---

## Modul 2 — Dashboard Utama

**Route:** `/`, `/dashboard`  
**Controller:** `PortalController@dashboard`  
**Page:** `Lawangsewu/Dashboard.vue`  
**Status:** ✅ Running

| Fitur | Status | Catatan |
|---|---|---|
| Widget statistik perkara | ✅ | Dari SIPP cache |
| Status koneksi WA | ✅ | Pull dari WaCaraka health |
| Ringkasan antrian aktif | ✅ | |
| Link navigasi cepat | ✅ | |

---

## Modul 3 — WaCaraka (WhatsApp Operator Desk)

**Route:** `/wa-caraka`, `/wa-caraka/api/{action}`  
**Controller:** `WaCarakaController`, `WaCarakaWebhookController`  
**Services:** `WaCarakaService`, `WaCarakaConversationService`, `WaCarakaTicketService`, `WaCarakaChatbotService`  
**Page:** `Lawangsewu/WaCaraka/Index.vue`  
**Bridge:** `http://192.168.88.33:8790` (Node.js/Baileys)  
**Status:** ✅ Running aktif (1.443 pesan, 163 percakapan)

### Sub-fitur

| Fitur | Status | Catatan |
|---|---|---|
| Inbox percakapan real-time | ✅ | Reverb WebSocket |
| Kirim teks | ✅ | |
| Kirim media (gambar, file) | ✅ | Max 5MB |
| Reply/Quote pesan | ✅ | |
| Paste gambar dari clipboard | ✅ | |
| Context menu klik kanan pesan | ✅ | |
| Delete untuk saya (lokal) | ✅ | |
| **Recall pesan (delete for everyone)** | ✅ | Baru — endpoint `/unsend-message` |
| Handover percakapan antar operator | ✅ | `wa_caraka_handovers` table |
| Force takeover (admin) | ✅ | |
| **Tutup percakapan + salam otomatis** | ✅ | Baru — template dapat dikonfigurasi |
| **Tutup percakapan tanpa salam** | ✅ | Baru — `close-silent` action |
| Buka kembali percakapan (superadmin) | ✅ | |
| Alias/label kontak (CustomerMark) | ✅ | `wa_caraka_conversation_marks` |
| Pin percakapan | ✅ | |
| Filter inbox (all/mine/unclaimed/closed) | ✅ | |
| Pencarian percakapan | ✅ | |
| Statistik operator (per user) | ✅ | Bug `.value` di template sudah diperbaiki |
| Laporan WaCaraka (PDF) | ✅ | `/wa-caraka/reports` |
| Tickets sistem | ✅ | `wa_caraka_tickets` (0 tiket saat ini) |
| Chatbot / auto-reply | 🔧 | `WaCarakaChatbotService` ada, konfigurasi perlu dicek |
| Webhook inbound | ✅ | `WaCarakaWebhookController` |
| QR code pairing device | ✅ | Via admin panel |
| Animasi border composer (blue light) | ✅ | Baru — CSS conic-gradient spin |

### WaCaraka Admin Console (`/admin/wa-caraka`)

| Fitur | Status |
|---|---|
| Device control (restart/reconnect/disconnect) | ✅ |
| QR pairing live | ✅ |
| Log pesan terkirim | ✅ |
| Broadcast ke banyak nomor | ✅ |
| Statistik real-time | ✅ |
| Background chat (konfigurasi) | ✅ |
| **Template pesan salam penutup** | ✅ | Baru |
| Clear history / inbox | ✅ |

---

## Modul 4 — Chat Internal

**Route:** `/chat`  
**Controller:** `ChatController`  
**Page:** `Lawangsewu/Chat.vue`  
**Status:** ⚠️ Running, tapi 0 pesan (tidak aktif digunakan)

| Fitur | Status | Catatan |
|---|---|---|
| Kirim pesan teks | ✅ | |
| Upload media | ✅ | |
| Hapus pesan | ✅ | |
| Clear semua | ✅ | |
| Alias pengguna | ✅ | `chat_aliases` table |
| Real-time via Reverb | ✅ | |

---

## Modul 5 — CCTV Monitor

**Route:** `/cctv`  
**Controller:** `PortalController@cctv`  
**Page:** `Lawangsewu/Cctv.vue`  
**Admin:** `Admin/CctvManager.vue`  
**Status:** ✅ Running (19 kamera terdaftar)

| Fitur | Status |
|---|---|
| Grid live stream kamera | ✅ |
| Tambah/edit kamera (admin) | ✅ |
| Refresh per-kamera | ✅ |
| Full-screen view | ✅ |

---

## Modul 6 — Antrian PTSP

**Route:** `/ptsp-queue` (TBC dari route list)  
**Controller:** `PtspQueueController`  
**Page:** `Lawangsewu/PtspQueue.vue`  
**Status:** ⚠️ Running, 1 tiket di DB

| Fitur | Status |
|---|---|
| Ambil nomor antrian | ✅ |
| Display monitor antrian | ✅ |
| Manajemen loket | ✅ |
| Reset antrian harian | ✅ |

---

## Modul 7 — Antrian Sidang

**Route:** Terintegrasi di widget & `/antrian-sidang`  
**Controller:** `SidangQueueController`  
**Page:** `Lawangsewu/SidangQueue.vue`  
**Status:** ⚠️ Running, 0 tiket aktif

| Fitur | Status |
|---|---|
| Antrian sidang per ruang | ✅ |
| Monitor real-time | ✅ |
| Widget embed publik | ✅ |

---

## Modul 8 — TDMS (Technology & Device Management System)

**Route:** `/tdms`, `/tdms/api/{action}`  
**Controller:** `TdmsController`  
**Page:** `Lawangsewu/Tdms/`  
**Status:** ⚠️ Running, 0 aset terdaftar (belum diisi)

| Fitur | Status | Catatan |
|---|---|---|
| Inventori aset IT | ✅ | Table ready, belum ada data |
| QR code per aset | ✅ | |
| Jadwal pemeliharaan | ✅ | |
| Catatan servis | ✅ | |
| Riwayat pergantian komponen | ✅ | |

---

## Modul 9 — SIPP Hub (Data Perkara)

**Route:** `/sipp-hub`  
**Controller:** `SippHubController`  
**Service:** `SippService`  
**Page:** Terintegrasi di Dashboard  
**Status:** ✅ Running (3 cache entries)

| Fitur | Status | Catatan |
|---|---|---|
| Tampilan statistik perkara | ✅ | |
| Refresh cache dari SIPP | ✅ | |
| Cache perkara harian | ✅ | |

---

## Modul 10 — Buku Tamu (Guestbook)

**Route:** `/buku-tamu`  
**Controller:** `GuestbookController`  
**Status:** ✅ Running aktif (440 entri)

| Fitur | Status |
|---|---|
| Input tamu digital | ✅ |
| Review & moderasi | ✅ |
| Laporan & ekspor | ✅ |
| Konfigurasi | ✅ |

---

## Modul 11 — Pilar Sidang (PILAR Semarang)

**Route:** `/pilar-smg`  
**Controller:** `PortalController@pilar`  
**Service:** `PilarQueueAuthority`  
**Page:** `Lawangsewu/Pilar.vue`  
**Status:** ✅ Running

---

## Modul 12 — Satellite / Pendopo Monitor

**Route:** `/satellite/pendopo`  
**Status:** ⚠️ Route terdaftar tapi handler berupa closure inline, perlu verifikasi

---

## Modul 13 — Widget Embed (Publik)

**Route:** Banyak — lihat `routes/web.php` prefix publik  
**Controller:** `WidgetCompatController`  
**Folder:** `widgets/` (PHP standalone)  
**Status:** ✅ Running (banyak widget aktif)

| Widget | Status |
|---|---|
| Statistik perkara | ✅ |
| Dashboard perkara | ✅ |
| Dashboard hakim | ✅ |
| Monitor persidangan | ✅ |
| Antrian sidang | ✅ |
| Info persidangan (berbagai tema) | ✅ |
| Pengumuman peradilan (RSS) | ✅ |
| Radius ghaib & kecamatan | ✅ |
| Biaya perkara | ✅ |
| Dashboard eCourt | ✅ |
| Statistik eCourt | ✅ |
| Monitor WA | ✅ |
| Berita pengadilan | ✅ |

---

## Modul 14 — Admin Panel

**Route prefix:** `/admin`  
**Status:** ✅ Running (26 route terdaftar)

| Fitur Admin | Status |
|---|---|
| Manajemen pengguna | ✅ |
| Google Allowlist | ✅ |
| Feature permissions (granular per user/role) | ✅ |
| Analytics penggunaan | ✅ |
| System Monitor | ✅ |
| Laporan (generate PDF/Excel) | ✅ |
| OAuth2 client management | ✅ |
| Pendopo/Guestbook settings | ✅ |
| CCTV manager | ✅ |
| WaCaraka console | ✅ |

---

## Modul 15 — System Monitor & Health

**Route:** `/health`, `/admin/system-monitor`  
**Controller:** `HealthController`, `Admin/SystemMonitorController`  
**Service:** `SystemMonitorService`, `HealthCheckService`  
**Status:** ✅ Running

---

## Modul 16 — Tailscale Dashboard

**Route:** `/tailscale`  
**Page:** `Tailscale/`  
**Service:** `TailscaleService`  
**Status:** 🔧 Ada infrastruktur, tergantung Tailscale VPN

---

## Ringkasan Status Keseluruhan

| Kategori | Jumlah Modul | ✅ | ⚠️ | ❌ |
|---|---|---|---|---|
| Core auth & access | 1 | 1 | 0 | 0 |
| Operasional (WA, Chat, Antrian) | 5 | 3 | 2 | 0 |
| Manajemen (TDMS, SIPP) | 2 | 1 | 1 | 0 |
| Publik (Widget, Guestbook) | 2 | 2 | 0 | 0 |
| Admin & Monitor | 3 | 3 | 0 | 0 |
| Infrastruktur (Queue, WS) | 2 | 2 | 0 | 0 |
| **Total** | **15+** | **12** | **3** | **0** |

**Catatan:** Tidak ada modul yang benar-benar mati. Modul ⚠️ berjalan tapi belum/kurang aktif digunakan atau ada konfigurasi opsional yang belum diset.
