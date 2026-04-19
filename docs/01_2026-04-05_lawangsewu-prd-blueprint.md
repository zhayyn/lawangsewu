# 🚪 PRD & Blueprint — Lawangsewu | Layanan Aplikasi Web Pengadilan Agama Semarang dan Workspace Utama Berupa Ekosistem Digital Internal PA Semarang
**Portal Utama Digitalisasi Pengadilan Agama Semarang (V.2)**

---

> **Filosofi & Analogi Besar:**
> "Lawangsewu" (Seribu Pintu) melambangkan sebuah ekosistem besar dengan banyak pintu layanan (aplikasi) yang dikelola terpusat.
> - **Lawangsewu Core (Laravel 13)** = Gedung utama, sistem keamanan pusat, lorong utama, dan ruang server utama (SSO).
> - **Pintu 1: Antrian PTSP & Antrian Sidang** = Ruang khusus pelayanan antrian publik.
> - **Pintu 2: Buku Tamu** = Ruang khusus registrasi tamu & kehadiran.
> - **Pintu 3: SIPP Hub** = Jalur khusus sinkronisasi data SIPP MA, berisi widget tabel dan statistik.
> - **Pintu 4: Jatidiri** = Ruang layanan identitas dan kepegawaian.
> - **SSO (Passport)** = Kartu ID (Kunci Master) yang berada **dalam 1 folder yang sama** dengan Lawangsewu. Memungkinkan staf masuk ke semua pintu tanpa login ulang.

---

## 📌 1. Executive Summary

Demi alasan penghematan anggaran (tidak bayar sewa Google Cloud/Firebase), kecepatan pengembangan, dan **Kedaulatan Data**, seluruh arsitektur yang tadinya terpencar kini disatukan secara kuat di dalam ranah **Laravel 13 Termutakhir** (telah di-upgrade dari versi 11).

| Item | Detail |
|:---|:---|
| **Keluarga/Nama Ekosistem** | **Lawangsewu ekosistem digital internal** |
| **Aplikasi Platform Utama** | **Lawangsewu Core** (dibangun dengan Laravel 13) |
| **Fungsi Platform Utama** | Pusat SSO, Manajemen User Global, Chatroom antar user, Modul CCTV, Dashboard Integrasi |
| **Aplikasi Terintegrasi (Satelit)** | Pintu-pintu: **Buku Tamu, Antrian PTSP, Antrian Sidang, SIPP Hub, Jatidiri (Kepegawaian), PTIP, Umum/Keuangan, Pandanaran AI**, dll |
| **Teknologi Core** | Laravel 13, PHP 8.3, Laravel Passport (OAuth2 SSO), Laravel Socialite (Login via Google), Laravel Reverb (WebSockets real-time untuk Chat), Vue 3 (Inertia.js) |
| **Database** | MariaDB |

---

## 🏗️ 2. Arsitektur Infrastruktur Lawangsewu (Sesuai Kondisi Real Tata Kerja)

```text
┌──────────────────────────────────────────────────────────────────────────────┐
│                      EKOSISTEM DIGITAL LAWANGSEWU                            │
│                                                                              │
│                   ┌──────────────────────────────────┐                       │
│                   │         LAWANGSEWU CORE          │                       │
│                   │   (Laravel 13 + Vue 3 Inertia)   │                       │
│                   │                                  │                       │
│                   │  ┌───────┐ ┌────────┐ ┌───────┐  │                       │
│                   │  │🔑 SSO │ │💬 Chat │ │🎥 CCTV│  │                       │
│                   │  │Passprt│ │ Reverb │ │iFrame │  │                       │
│                   │  └───────┘ └────────┘ └───────┘  │                       │
│                   └─────────────────┬────────────────┘                       │
│                                     │  (Semua di dalam /var/www/lawangsewu)  │
│      ┌──────────────────────────────┼──────────────────────────────┐         │
│      │               OAuth2 API / Central Integration Bus          │         │
│      └┬───────┬───────┬───────┬─────┴─┬───────┬───────┬───────┬────┴────┬─┐  │
│       │       │       │       │       │       │       │       │         │ │  │
│  ┌────▼─┐ ┌───▼──┐ ┌──▼───┐ ┌─▼────┐ ┌▼─────┐┌▼─────┐┌▼─────┐┌▼───────┐ │ │  │
│  │ BUKU │ │ ANTRI│ │ ANTRI│ │ SIPP │ │ KEPEG││ PTIP ││ UMUM/││ PANDA  │ │ │  │
│  │ TAMU │ │ PTSP │ │ SDNG │ │ HUB  │ │ AWAIAN││      ││ KEU  ││ NARAN AI│ │ │  │
│  └──────┘ └──────┘ └──────┘ └──────┘ └──────┘└──────┘└──────┘└────────┘ │ │  │
│                                  ... (Akan datang: Kepaniteraan, Hakim)   │  │
└───────────────────────────────────────────────────────────────────────────┴──┘
```

---

## 📁 3. Pemetaan Modul & Ekosistem (Single Folder Structure)

Semua modul akan dibungkus rapi dalam *routing* dan kontrol **Lawangsewu Core** `/var/www/lawangsewu` agar semuanya 1 pintu:

1. **SSO Login (Identity Hub):** 
   Laravel Passport + Socialite. Berada di dalam folder Lawangsewu. Staf wajib login untuk mengakses semua modul di bawahnya.
2. **Chatroom & CCTV:**
   Modul komunikasi internal *real-time* antar pegawai dan pemantauan CCTV pengadilan.
3. **Buku Tamu:** 
   Pengganti app lama (Pendopo). Buku tamu digital.
4. **Antrian PTSP:** 
   Mengambil alih fungsi layanan meja Pelayanan Terpadu Satu Pintu.
5. **Antrian Sidang:** 
   Sistem panggilan persidangan secara terpisah namun terintegrasi.
6. **SIPP Hub:** 
   Berada langsung sebagai modul di dalam Lawangsewu. Menampilkan widget-widget PHP, tabel, dan statistik hasil query langsung dari master data.
7. **Jatidiri (Kepegawaian):** 
   Sistem presensi, surat cuti, dan SDM internal.
8. **PTIP & Umum/Keuangan:** 
   Dashboard rekapitulasi pelaporan PTIP, inventaris umum, dan keuangan instansi.
9. **Pandanaran AI (Chatbot):** 
   Layanan Artificial Intelligence asisten internal pengadilan.
10. **Kepaniteraan & Hakim:** *(Segera disusul sesuai Roadmap ke depan).*

---

## 🎨 4. UI/UX Master Blueprint & Navigasi

Tampilan dirender menggunakan **Vue 3 (Inertia.js) + Tailwind CSS** tanpa *loading page*.

```text
======================================================================
[ ≡ ] Lawangsewu V2              🔍 Cari Data...          [ 🧑 Akun ▾ ]
======================================================================
 🛡️ MENU UTAMA          |  DASHBOARD (SIPP HUB & WIDGET STATISTIK)
 ▣ Home / Dashboard     |---------------------------------------------
                        |  [ Widget Antrian ]        [ Jadwal Sidang ]
 🏛️ PELAYANAN           |  Sisa PTSP: 34 Orang       Total Hari ini: 12
   ├─ Buku Tamu         |  
   ├─ Antrian PTSP      |  [ Kamera CCTV 🎥 ]
   ├─ Antrian Sidang    |  [ R.Tgg ] [ PTSP ] [ R.Sdg ] [ Depan ]
                        |  (Klik untuk streaming iframe)
 ⚖️ SIPP HUB & DATA     |  
   ├─ Widget Statistik  |---------------------------------------------
                        | 💬 CHAT INTERNAL & INTERKOM
 👥 ORGANISASI          | Alias Publik Anda: [ Bima_Sakti_01 ✎ ]
   ├─ Kepegawaian       | 
   ├─ PTIP              | 🟢 Bima_Sakti_01: "Bu, meja 3 blangko habis."
   ├─ Umum / Keuangan   | 🟢 Arjuna_Hukum: "Siap, langsung kirim."
   ├─ Pandanaran AI     | [ Ketik pesan di sini... ]          [Kirim]
                        |
 ⚙️ PENGATURAN          | 
======================================================================
```

---

## 🗄️ 5. Skema Database Inti (MariaDB)

Wajib menggunakan relasi *Foreign Key* kaku di dalam database MariaDB `lawangsewu_core`.

- **Tabel Identitas, Keamanan & SSO:**
  - `users`: ID, nama, NIP, email_google, avatar, password.
  - `roles` & `permissions`: Role base matrix.
  - `oauth_clients`: Mencatat koneksi JWT.
- **Tabel Fitur Komunikasi (Chatroom & Alias):**
  - `chat_aliases`: Karena user memiliki nama resmi, tabel ini menyimpan *Alias Publik* khusus untuk ber-chat ria yang bisa diganti-ganti sesuka hati oleh pemilik akun (*nickname*).
  - `chat_messages`: ID, user_id (pelacak log asli), alias_tampil, pesan.
  - `cctv_cameras`: Menyimpan link `iframe_src` kamera PA Semarang.
- **Tabel Operasional Pengadilan:**
  - `antrian_ptsp_tickets`: Trafik loket Pelayanan Terpadu.
  - `antrian_sidang_tickets`: Trafik pergerakan para pihak ke ruang sidang.
  - `sipp_widgets_cache`: Area SIPP Hub menyimpan data/cache statistik dari server pusat agar cepat waktu dimuat.

---

## 🚀 6. Revisi Roadmap Eksekusi Terukur (Action Plan)

### ✅ SPRINT 0 — Persiapan Server & Workspace (Selesai Bekerja)
- Ruang `/var/www/lawangsewu` sudah tegak berdiri.

### ⏳ SPRINT 1 — Identitas SSO, Chatroom, & CCTV
- Tarik kerangka **Vue 3 (Inertia) + Tailwind CSS + Laravel Breeze**.
- Pasang **Laravel Socialite (Login Google)**.
- Bangun UI Dashboard untuk CCTV List (Lazy Load I-Frame).
- Setup **Laravel Reverb** untuk fungsi Chatroom dan sistem pengaturan ID Alias (*Nickname*).

### ⏳ SPRINT 2 — Pelayanan (Buku Tamu, Antrian PTSP, Sidang)
- Modul pencatatan Buku Tamu tersentralisasi.
- Konstruksi logika pergerakan suara panggilan tiket PTSP dan Persidangan.

### ⏳ SPRINT 3 — SIPP Hub & Analitik Murni
- Koneksi sinkronisasi data MA.
- Menyusun layar widget laporan (meniru grafik lama dan di modernisasi dengan Tailwind UI).

### ⏳ SPRINT 4 — Organisasi (Kepegawaian, PTIP, Umum, AI)
- Panel kontrol data SDM, Keuangan, dan interkoneksi ke robot Pandanaran AI.
- Hardening dan Rilis Produksi Akhir.

---
*Dokumen ini merupakan panduan tunggal sistematis (Single Source of Truth) untuk Pimpinan Pejabat Pembuat Komitmen dan Tim Engineering PA Semarang.*
