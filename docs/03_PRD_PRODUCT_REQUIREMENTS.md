# 03 — PRD (Product Requirements Document)
# Lawangsewu V2 — Portal Digital Terpadu PA Semarang

> **Versi:** 2.0  
> **Status:** Active Development (Production)  
> **Dibuat:** 2026-05-08 (rekonstruksi dari kondisi aktual)  
> **Owner:** Pengadilan Agama Semarang — Tim IT  

---

## 1. Executive Summary

Lawangsewu V2 adalah portal digital internal Pengadilan Agama Semarang yang dibangun untuk mempercepat dan mengintegrasikan operasional sehari-hari: komunikasi dengan masyarakat via WhatsApp, monitoring antrian layanan, manajemen aset IT, dan akses data perkara SIPP — semuanya dalam satu platform terpadu yang dapat diakses oleh seluruh pegawai.

---

## 2. Latar Belakang & Masalah yang Diselesaikan

### Masalah Sebelumnya
1. **Komunikasi WhatsApp tidak terstruktur** — operator menjawab pesan dari HP pribadi, tidak ada riwayat, tidak ada pembagian beban kerja
2. **Data perkara tersebar** — harus buka SIPP secara terpisah, tidak ada ringkasan terintegrasi
3. **Antrian manual** — nomor antrian PTSP dan sidang masih ditulis tangan
4. **Aset IT tidak terdokumentasi** — tidak ada sistem pelacakan perangkat dan jadwal servis
5. **Monitoring CCTV tidak terpusat** — harus masuk ke DVR masing-masing
6. **Buku tamu masih fisik** — data tidak terdigitalisasi

### Solusi Lawangsewu V2
- **WaCaraka**: Operator desk WhatsApp berbasis web — multi-operator, real-time, riwayat lengkap
- **SIPP Hub**: Agregasi data perkara dengan cache — tampil di dashboard tanpa login SIPP
- **Antrian Digital**: Sistem nomor antrian PTSP dan sidang berbasis web
- **TDMS**: Inventori aset IT dengan QR code, jadwal maintenance, riwayat servis
- **CCTV Monitor**: Grid live stream semua kamera dalam satu halaman
- **Buku Tamu Digital**: Form digital dengan backend moderasi dan laporan

---

## 3. Target Pengguna

| Persona | Role | Kebutuhan Utama |
|---|---|---|
| **Operator WA** | `operator` | Menjawab pesan WA, handover, laporan harian |
| **Petugas PTSP** | `operator` | Input & tampilkan antrian, monitor layanan |
| **Staf IT** | `operator` / `admin` | Monitor CCTV, kelola aset, monitor sistem |
| **Pimpinan** | `viewer` / `admin` | Dashboard statistik, laporan, monitor WA |
| **Admin Sistem** | `admin` / `superadmin` | Kelola user, permission, konfigurasi |

---

## 4. Goals & Non-Goals

### Goals ✅
- Sentralisasi komunikasi WhatsApp PA Semarang dalam satu platform multi-operator
- Digitalisasi proses antrian layanan publik
- Visibilitas real-time terhadap operasional harian (WA, antrian, CCTV, perkara)
- Sistem manajemen aset IT dengan maintenance tracking
- Widget embeddable untuk website publik PA Semarang

### Non-Goals ❌
- **Bukan** sistem persidangan (itu SIPP)
- **Bukan** sistem kepegawaian/SDM
- **Bukan** sistem e-court (itu domain Mahkamah Agung)
- **Bukan** website publik (hanya widget yang di-embed)

---

## 5. Fitur Utama (Feature Set)

### 5.1 WaCaraka — WhatsApp Operator Desk

**Priority: P0 (Core)**

#### Messaging
- [ P0 ] Inbox percakapan real-time dengan WebSocket (Reverb)
- [ P0 ] Kirim teks, media, quote/reply
- [ P0 ] Paste gambar dari clipboard
- [ P0 ] Recall pesan (delete for everyone) — max 60 menit
- [ P1 ] Template pesan cepat

#### Conversation Management
- [ P0 ] Ownership percakapan (claim/handover/force-takeover)
- [ P0 ] Tutup percakapan dengan salam otomatis (template dapat dikonfigurasi)
- [ P0 ] Tutup percakapan tanpa salam
- [ P0 ] Alias & label kontak (CustomerMark)
- [ P0 ] Pin percakapan penting
- [ P1 ] Filter & pencarian inbox

#### Reporting & Analytics
- [ P0 ] Statistik per operator (pesan dibalas, percakapan ditangani)
- [ P1 ] Laporan harian/mingguan/bulanan (PDF)
- [ P2 ] Dashboard tren WA (grafik)

#### Admin
- [ P0 ] Device management (QR pairing, restart, reconnect)
- [ P0 ] Template pesan penutup (configurable)
- [ P0 ] Background chat (configurable)
- [ P1 ] Broadcast ke banyak nomor
- [ P2 ] Chatbot / auto-reply terprogram

### 5.2 Antrian Digital — PTSP & Sidang

**Priority: P1**

- [ P0 ] Generate nomor antrian digital
- [ P0 ] Monitor antrian real-time
- [ P0 ] Manajemen loket/counter
- [ P0 ] Widget publik untuk display TV antrian
- [ P1 ] Reset otomatis harian
- [ P2 ] SMS/WA notifikasi ke pemohon

### 5.3 TDMS — Asset Management

**Priority: P1**

- [ P0 ] Inventori aset IT (laptop, printer, server, dll)
- [ P0 ] QR code per aset — scan untuk detail
- [ P0 ] Jadwal pemeliharaan berkala
- [ P0 ] Catatan riwayat servis
- [ P1 ] Riwayat pergantian komponen
- [ P2 ] Notifikasi jatuh tempo maintenance

### 5.4 CCTV Monitor

**Priority: P1**

- [ P0 ] Grid live stream semua kamera
- [ P0 ] Tambah/edit kamera via admin (RTSP/HLS URL)
- [ P1 ] Full-screen view per kamera
- [ P2 ] Recording / clip download

### 5.5 Widget Publik (Embed)

**Priority: P1**

- [ P0 ] Statistik perkara (grafik tahunan)
- [ P0 ] Dashboard perkara bulanan
- [ P0 ] Monitor persidangan hari ini
- [ P0 ] Info antrian sidang
- [ P0 ] Pengumuman peradilan (RSS)
- [ P1 ] Biaya perkara & radius ghaib
- [ P2 ] Dashboard hakim

---

## 6. Arsitektur & Constraints

### Tech Stack (LOCKED — tidak boleh diubah tanpa approval)

```
Backend:    Laravel 11 + PHP 8.3
Frontend:   Vue 3 + Inertia.js + Vite 6
CSS:        Tailwind CSS
Database:   MySQL
Cache:      File (dapat di-upgrade ke Redis)
Queue:      Database (dapat di-upgrade ke Redis)
WebSocket:  Laravel Reverb
WA Bridge:  Node.js/Baileys (terpisah, akses via HTTP)
Auth:       Session + Google OAuth2
```

### Constraints
1. Sistem harus dapat berjalan di VPS spek menengah (RAM 4GB, 2 vCPU)
2. Semua halaman harus responsif (desktop operator & mobile admin)
3. WaCaraka harus real-time — latency < 2 detik untuk pesan baru
4. Widget embed harus dapat diakses tanpa login (public endpoint)
5. Semua data komunikasi wajib tersimpan di DB (audit trail)

---

## 7. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| WA Bridge (Server 33) mati | WaCaraka tidak bisa kirim/terima pesan | Health check monitoring, restart script |
| Queue worker berhenti | Pesan WA outbound tidak terkirim | Supervisor atau cron restart |
| Reverb WebSocket disconnect | Inbox tidak real-time, harus manual refresh | Auto-reconnect di frontend, fallback polling |
| Akun WhatsApp diblokir WA | Seluruh layanan WA terhenti | Backup nomor, SOP eskalasi |
| Data perkara SIPP tidak sync | Widget statistik stale | Cache TTL + manual refresh button |

---

## 8. Acceptance Criteria (Definition of Done)

### WaCaraka
- Operator dapat menjawab pesan dalam < 5 detik setelah masuk
- Handover percakapan berfungsi tanpa kehilangan riwayat
- Laporan operator terhitung akurat (verifikasi: jumlah pesan = DB count)
- Recall pesan bekerja dalam window 60 menit

### Antrian
- Nomor antrian tidak duplikat dalam satu hari
- Display publik refresh otomatis tanpa reload manual

### TDMS
- QR code dapat discan dan menampilkan detail aset lengkap
- Jadwal maintenance muncul di kalender

---

## 9. Roadmap (Backlog Prioritas)

### Short-term (1-3 bulan)
- [ ] Isi data aset TDMS (saat ini 0 aset)
- [ ] Aktifkan chatbot WaCaraka untuk jam non-kerja
- [ ] Setup Supervisor untuk auto-restart queue worker & Reverb
- [ ] Redis upgrade untuk cache & queue (performa)

### Mid-term (3-6 bulan)
- [ ] Notifikasi WA otomatis dari antrian sidang
- [ ] Dashboard analytics WaCaraka dengan Chart.js
- [ ] Mobile-optimized view untuk operator lapangan
- [ ] Integrasi SOP/panduan digital dalam TDMS

### Long-term (6-12 bulan)
- [ ] Chatbot AI untuk FAQ hukum otomatis
- [ ] API publik terstandar untuk data perkara
- [ ] Multi-device WaCaraka (lebih dari 1 nomor WA)
- [ ] SSO dengan sistem kepegawaian PA
