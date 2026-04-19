# ⚔️ SenopaTEA — Framework Agen Otonom untuk Lawangsewu

> **Tanggal:** 15 April 2026  
> **Kategori:** Developer Workflow & AI Agent Orchestration  
> **Status:** Aktif  
> **Versi:** 1.0 — Adapted for Lawangsewu V2

---

## Daftar Isi

1. [Apa Itu SenopaTEA?](#1-apa-itu-senopatea)
2. [Analogi: Tim Pembangunan Candi](#2-analogi-tim-pembangunan-candi)
3. [Struktur File](#3-struktur-file)
4. [Empat Agent dan Perannya](#4-empat-agent-dan-perannya)
5. [Tiga Workflow yang Tersedia](#5-tiga-workflow-yang-tersedia)
6. [File Konteks (context.md)](#6-file-konteks-contextmd)
7. [Cara Kerja Rantai Komando](#7-cara-kerja-rantai-komando)
8. [Contoh Penggunaan Nyata](#8-contoh-penggunaan-nyata)
9. [Aturan Universal](#9-aturan-universal)
10. [FAQ](#10-faq)

---

## 1. Apa Itu SenopaTEA?

**SenopaTEA** (TEA = The Evilsign Army) adalah framework yang mengatur bagaimana AI coding assistant bekerja saat mengembangkan Lawangsewu. Ia memecah satu AI menjadi **empat persona** yang masing-masing punya peran, batasan, dan protokol eksekusi yang tegas.

### Bukan Software, Tapi Aturan Main

SenopaTEA **bukan** fitur aplikasi. Ia tidak muncul di tampilan Lawangsewu, tidak berjalan di server, dan tidak mempengaruhi pengguna akhir. SenopaTEA adalah **dokumen instruksi** (markdown) yang disimpan di folder `.agents/` yang mengatur *bagaimana AI bekerja saat develop kode*.

| Yang BUKAN SenopaTEA | Yang ADALAH SenopaTEA |
|---|---|
| ❌ Software monitoring | ✅ Panduan kerja untuk AI developer |
| ❌ Fitur di dashboard | ✅ File konfigurasi di `.agents/` |
| ❌ Backend service | ✅ Protokol dan workflow document |
| ❌ Menambah beban server | ✅ Zero overhead — hanya teks |

---

## 2. Analogi: Tim Pembangunan Candi

Bayangkan Lawangsewu adalah **candi yang sedang dibangun**. Tanpa SenopaTEA, proses pembangunannya seperti ini:

### ❌ Tanpa SenopaTEA (Satu Tukang Serba Bisa)

> Kamu punya **satu tukang** yang melakukan segalanya:
> 
> - Mendesain blueprint 📐
> - Memahat batu ⛏️
> - Mengecek kualitas batu 🔍
> - Memasang batu ke candi 🏗️
> 
> Masalahnya? Tukang ini kadang langsung pahat batu **tanpa blueprint**, atau langsung pasang batu **tanpa dicek kualitasnya**. Hasilnya? Candi miring, batu retak, dan harus bongkar pasang ulang.

### ✅ Dengan SenopaTEA (Tim Spesialis Terorganisir)

> Sekarang kamu punya **empat spesialis** dengan rantai komando yang jelas:
> 
> 1. 📐 **@pm — Sang Arsitek**
>    > *"Saya gambar blueprint-nya dulu. Tuan, ini rencananya. Setuju?"*
>    > 
>    > Tidak ada batu yang dipahat sebelum blueprint disetujui.
> 
> 2. ⛏️ **@engineer — Sang Pemahat**
>    > *"Blueprint sudah disetujui. Saya pahat batu sesuai ukuran yang diminta. Persis. Tidak lebih, tidak kurang."*
>    > 
>    > Tidak berimprovisasi. Mengikuti blueprint secara rigid.
> 
> 3. 🔍 **@qa — Sang Pengawas Mutu**
>    > *"Batu sudah dipahat. Saya cek: ada retakan? Ukurannya benar? Kuat menahan beban? ...Ada cacat di sisi kiri. Saya perbaiki."*
>    > 
>    > Skeptis terhadap semua batu. Tidak percaya sampai dibuktikan.
> 
> 4. 🏗️ **@devops — Sang Pemasang**
>    > *"Batu sudah lolos quality control. Saya pasang ke candi, verifikasi posisinya stabil, lalu buka gerbang untuk pengunjung."*
>    > 
>    > Memastikan candi berdiri kokoh dan bisa dikunjungi.

**Hasilnya?** Setiap batu yang terpasang di candi sudah melalui 4 tahap verifikasi. Tidak ada batu asal-asalan.

---

## 3. Struktur File

```
lawangsewu/
└── .agents/                          # 📁 Markas SenopaTEA
    ├── agents.md                     # 🤖 Daftar identitas & role semua agent
    ├── context.md                    # 🏛️ Peta lengkap arsitektur Lawangsewu
    ├── skills/                       # ⚙️ Kemampuan masing-masing agent
    │   ├── write_specs.md            #    └── @pm: cara bikin spesifikasi
    │   ├── generate_code.md          #    └── @engineer: cara bikin kode
    │   ├── audit_code.md             #    └── @qa: cara audit & cek keamanan
    │   └── deploy_app.md             #    └── @devops: cara deploy & serve
    └── workflows/                    # 🚀 Skenario kerja tim
        ├── startcycle.md             #    └── Full cycle (fitur baru)
        ├── hotfix.md                 #    └── Quick fix (perbaikan minor)
        └── audit.md                  #    └── Health check (audit saja)
```

### Analogi Struktur

| File/Folder | Analogi Tim Candi |
|---|---|
| `agents.md` | Kartu identitas setiap spesialis + rantai komando |
| `context.md` | Peta situs candi: dimensi, material, fondasi yang sudah ada |
| `skills/write_specs.md` | Manual kerja arsitek: cara gambar blueprint |
| `skills/generate_code.md` | Manual kerja pemahat: cara pahat batu |
| `skills/audit_code.md` | Manual kerja pengawas: checklist quality control |
| `skills/deploy_app.md` | Manual kerja pemasang: cara pasang & buka gerbang |
| `workflows/startcycle.md` | SOP pembangunan lengkap (blueprint → pahat → cek → pasang) |
| `workflows/hotfix.md` | SOP perbaikan darurat (tambal retakan tanpa redesain) |
| `workflows/audit.md` | SOP inspeksi rutin (cek kondisi tanpa renovasi) |

---

## 4. Empat Agent dan Perannya

### 📐 @pm — Sang Arsitek (Lead Architect)

| Atribut | Detail |
|---|---|
| **Tugas** | Analisis ide → buat Technical Specification |
| **Output** | Dokumen spec di `docs/` |
| **Constraint utama** | WAJIB halt dan minta approval sebelum lanjut ke @engineer |
| **Kapan aktif** | Awal setiap fitur baru (`/startcycle`) |

**Analogi:** Arsitek yang menggambar blueprint dan menjelaskannya ke klien. *"Ini rancangan saya, Tuan. Pintu di sini, jendela di sana. Setuju?"* Tidak ada satu batu pun yang disentuh sebelum klien tanda tangan.

#### Yang Diperiksa @pm:
- Apakah ide bisa diterapkan dengan tech stack yang ada?
- Modul mana yang terdampak?
- Butuh tabel database baru?
- Ada perubahan hak akses (RBAC)?
- Ada konflik dengan fitur existing?

---

### ⛏️ @engineer — Sang Pembangun (Full-Stack Developer)

| Atribut | Detail |
|---|---|
| **Tugas** | Implementasi kode sesuai spec yang diapprove |
| **Output** | File kode langsung di struktur Lawangsewu |
| **Constraint utama** | DILARANG berasumsi — ikuti spec secara rigid |
| **Kapan aktif** | Setelah spec diapprove (`/startcycle`) atau saat `/hotfix` |

**Analogi:** Pemahat batu yang mahir tapi disiplin. Kalau blueprint bilang *"batu 30cm × 20cm"*, dia pahat persis 30 × 20. Tidak pernah *"harusnya 35 lebih bagus ya"*. Jika spec kurang jelas, dia bertanya ke arsitek, bukan berasumsi.

#### Urutan Build:
```
Migration → Model → Service → Controller → Route → Vue Page → Test
```
Seperti membangun rumah: fondasi dulu, baru dinding, baru atap, baru furnitur.

---

### 🔍 @qa — Sang Pengawas (Security & Logic Auditor)

| Atribut | Detail |
|---|---|
| **Tugas** | Audit kode: bug, celah keamanan, ketidaksesuaian spec |
| **Output** | Kode yang dipoles + Audit Report |
| **Constraint utama** | DILARANG mengubah arsitektur — hanya fix bug |
| **Kapan aktif** | Setelah @engineer selesai build, atau saat `/audit` |

**Analogi:** Inspektur bangunan yang paranoid. Dia ketuk setiap batu, cek setiap sambungan, goyangkan setiap pilar. *"Batu ini retak di bagian dalam. Ganti."* Dia tidak pernah bilang *"kayaknya aman"* — dia buktikan dengan tes.

#### 5 Layer Audit:

```
Layer 1: Syntax & Dependency     → "Semua bahan lengkap? Ada yang missing?"
Layer 2: Logic & Spec Compliance → "Sesuai blueprint? Tidak ada yang skip?"
Layer 3: Security                → "Ada pintu belakang yang tidak terkunci?"
Layer 4: Test Execution          → "Candi tahan gempa?" (run test suite)
Layer 5: Performance             → "Pengunjung bisa jalan lancar di dalam?"
```

---

### 🏗️ @devops — Sang Penjaga Gerbang (Deployment Wizard)

| Atribut | Detail |
|---|---|
| **Tugas** | Build, deploy, verifikasi environment |
| **Output** | Server berjalan + Deployment Report |
| **Constraint utama** | DILARANG deploy jika ada regresi test |
| **Kapan aktif** | Setelah @qa selesai audit (`/startcycle`), atau manual |

**Analogi:** Mandor yang bertanggung jawab membuka gerbang candi untuk publik. Sebelum buka, dia cek: *"Listrik nyala? Air mengalir? Pintu darurat berfungsi? CCTV aktif?"* Baru kemudian buka gerbang dan umumkan: *"Candi siap dikunjungi di alamat ini."*

#### Checklist DevOps Lawangsewu:
```
1. Install dependencies (composer, npm)
2. Build frontend (npm run build)
3. Clear & rebuild cache
4. Run database migration
5. Verify RBAC integrity
6. Run test suite
7. Start services (server, queue, websocket)
8. Report URL akses
```

---

## 5. Tiga Workflow yang Tersedia

### 🚀 `/startcycle` — Pembangunan Lengkap

**Kapan dipakai:** Membangun fitur baru atau perubahan signifikan.

**Analogi:** Bangun sayap candi baru dari nol. Butuh blueprint, tukang, quality check, dan grand opening.

```
User request
    │
    ▼
@pm ──→ Bikin spec ──→ ⛔ HALT ──→ User approve? ──→ YES
                                                        │
@engineer ──→ Build kode ──────────────────────────────→│
                                                        │
@qa ──→ Audit 5 layer ──→ Fix bug ─────────────────────→│
                                                        │  
@devops ──→ Deploy ──→ Serve ──→ Report ───────────────→│
                                                        │
                                                    📊 DONE
```

**Contoh:**
> *"Saya ingin tambah fitur notifikasi email ketika antrian dipanggil"*
> → Full cycle: spec → approve → build → audit → deploy

---

### 🔧 `/hotfix` — Perbaikan Cepat

**Kapan dipakai:** Bug fix, typo, styling issue, perbaikan minor.

**Analogi:** Ada batu retak di dinding candi. Tidak perlu redesain seluruh dinding — cukup ganti batu yang retak dan cek lagi.

```
User report bug
    │
    ▼
@engineer ──→ Fix langsung
    │
    ▼
@qa ──→ Run tests ──→ 0 regresi? ──→ ✅ Done
```

**Contoh:**
> *"Toast login tidak muncul setelah redirect dari Google"*
> → Quick fix: cari root cause → fix → test → done

**Batasan:** Tidak boleh ubah arsitektur, tambah fitur, atau ubah database schema. Jika butuh itu → eskalasi ke `/startcycle`.

---

### 🔍 `/audit` — Inspeksi Tanpa Renovasi

**Kapan dipakai:** Health check berkala, pre-launch security review.

**Analogi:** Inspeksi rutin candi setiap bulan. Cek retakan, cek fondasi, cek keamanan. Catat semua temuan dalam laporan, tapi **tidak renovasi**.

```
User: /audit [scope]
    │
    ▼
@qa ──→ Scan ──→ Report saja (tidak fix)
```

**Scope options:**

| Command | Yang Diaudit |
|---|---|
| `/audit all` | Seluruh codebase |
| `/audit security` | Celah keamanan saja |
| `/audit tests` | Test suite saja |
| `/audit rbac` | Integritas role & permission |
| `/audit ptsp` | Modul PTSP saja |

**Output:** Audit report dengan health score dan prioritas temuan (🔴 Critical / 🟡 Warning / 🟢 Info).

---

## 6. File Konteks (context.md)

File `context.md` adalah **peta lengkap Lawangsewu** yang wajib dibaca setiap agent sebelum bekerja. Tanpa file ini, agent seperti tukang bangunan yang datang ke lokasi tanpa denah.

### Isi context.md:

| Section | Fungsi | Analogi |
|---|---|---|
| **Tech Stack** | Daftar teknologi yang di-lock | Daftar material yang boleh dipakai (hanya batu andesit, bukan marmer) |
| **Arsitektur Modul** | Map seluruh modul Lawangsewu | Denah ruangan candi |
| **SSO & RBAC** | Sistem autentikasi & otorisasi | Peta pintu masuk dan siapa boleh masuk ke mana |
| **Database Models** | Seluruh tabel dan relasinya | Inventaris material dan ukurannya |
| **Konvensi Kode** | Aturan penamaan file, class, route | Standar ukuran dan penamaan batu |
| **Testing Conventions** | Cara menulis dan menjalankan test | Prosedur quality check |
| **Deployment Checklist** | Langkah-langkah deploy | SOP membuka gerbang candi |

### Mengapa context.md Penting?

Tanpa `context.md`, AI bisa membuat keputusan yang salah:

| Tanpa context.md | Dengan context.md |
|---|---|
| AI menyarankan pakai React | AI tahu harus pakai Vue 3 |
| AI buat route tanpa middleware | AI tahu wajib `auth → verified → active → role:xxx` |
| AI simpan file di lokasi random | AI tahu struktur folder Lawangsewu |
| AI pakai naming convention sendiri | AI ikuti konvensi `lawangsewu.[modul].[aksi]` |

---

## 7. Cara Kerja Rantai Komando

### Prinsip Utama

```
                    ┌──────────────┐
                    │  Tuan Muda   │  ← User (pemegang keputusan tertinggi)
                    │  (User)      │
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │     @pm      │  ← Analisis & Spec
                    │  (Arsitek)   │
                    └──────┬───────┘
                           │
                    ⛔ APPROVAL GATE ← User harus bilang YES
                           │
                    ┌──────▼───────┐
                    │  @engineer   │  ← Build
                    │  (Pembangun) │
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │     @qa      │  ← Audit & Fix
                    │  (Pengawas)  │
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │   @devops    │  ← Deploy & Serve
                    │  (Pemasang)  │
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │   📊 Report  │  ← Laporan ke User
                    └──────────────┘
```

### Aturan yang Tidak Boleh Dilanggar

1. **Approval Gate:** Tidak ada kode yang ditulis tanpa persetujuan user atas spec
2. **No Skip:** Agent tidak boleh melompati urutan (engineer tidak boleh deploy)
3. **No Architecture Change by QA:** QA hanya fix bug, tidak redesain
4. **No Deploy with Regression:** DevOps tidak deploy jika test gagal
5. **User is King:** Setiap keputusan desain dikembalikan ke user

---

## 8. Contoh Penggunaan Nyata

### Contoh 1: Fitur Baru (`/startcycle`)

```
User: "Saya ingin tambah fitur export buku tamu ke PDF"

@pm:
  ├── Baca context.md → mengerti arsitektur guestbook
  ├── Analisis: butuh library PDF, controller method baru, 
  │   route baru, tombol di Vue
  ├── Bikin spec: GuestbookController@export, route /buku-tamu/export,
  │   pakai DomPDF, middleware role:operator,admin
  └── "Tuan Muda, ini spesifikasinya. Approve?"

User: "YES"

@engineer:
  ├── composer require barryvdh/laravel-dompdf
  ├── Bikin GuestbookController@export method
  ├── Tambah route: lawangsewu.guestbook.export
  ├── Tambah tombol "Export PDF" di GuestbookList.vue
  └── Bikin test: test_operator_can_export_guestbook_pdf

@qa:
  ├── Cek: route punya middleware? ✅
  ├── Cek: SQL injection di query? ✅ aman (Eloquent)
  ├── Cek: file size limit? ⚠️ tambahkan limit 1000 row
  ├── Run tests: 75 pass ✅
  └── Audit report: Health Score 95/100

@devops:
  ├── composer install
  ├── npm run build
  ├── Clear & rebuild cache
  ├── php artisan test → 75 pass ✅
  └── "Server aktif di http://localhost:8000 ✅"
```

### Contoh 2: Bug Fix (`/hotfix`)

```
User: "Antrian PTSP tidak bisa dipanggil, muncul error 500"

@engineer:
  ├── Identifikasi: PtspQueueController@call → missing null check
  ├── Fix: tambah guard clause untuk ticket not found
  └── Tambah test: test_call_nonexistent_ticket_returns_404

@qa:
  ├── Run tests: 75 pass, 0 regresi ✅
  └── "Hotfix verified. Tidak ada efek samping."
```

### Contoh 3: Audit (`/audit security`)

```
User: "/audit security"

@qa:
  ├── Scan 87 routes...
  ├── 🟢 Semua POST/PATCH/DELETE dilindungi CSRF
  ├── 🟢 Semua route punya middleware auth
  ├── 🟡 Route /lawangsewu/api/pengumuman-rss bisa diakses guest
  │   (ini intentional — widget publik)
  ├── 🟢 Tidak ada SQL injection vector
  ├── 🟢 File upload di-validate (max 2MB)
  └── Audit Report: Health Score 98/100
      Recommendation: dokumentasikan route publik
      yang intentionally tanpa auth
```

---

## 9. Aturan Universal

Berlaku untuk **semua agent**, tanpa pengecualian:

| # | Aturan | Alasan |
|---|---|---|
| 1 | **Tech Stack LOCKED** | Tidak boleh introduce teknologi baru tanpa approval |
| 2 | **Bahasa Indonesia** | Semua komunikasi dengan user dalam Bahasa Indonesia |
| 3 | **Test Coverage** | Setiap perubahan harus punya test |
| 4 | **Docs Sync** | Perubahan signifikan harus didokumentasikan |
| 5 | **No Destructive Actions** | Tidak boleh drop table, delete file, atau ubah .env tanpa approval |
| 6 | **Context First** | Selalu baca `context.md` sebelum bertindak |

---

## 10. FAQ

### "Apakah SenopaTEA memperlambat development?"

**Tidak.** SenopaTEA justru mempercepat karena:
- Spec dibuat di awal → tidak ada bolak-balik di tengah build
- Bug ditangkap oleh @qa sebelum deploy → tidak ada patching darurat
- Context.md menghilangkan waktu "onboarding" AI ke arsitektur

### "Apakah SenopaTEA hanya untuk AI? Developer manusia bisa pakai?"

**Ya**, developer manusia bisa membaca file-file ini sebagai:
- `context.md` → Onboarding guide untuk developer baru
- `skills/` → Coding standards & best practices
- `workflows/` → SOP development

### "Bagaimana jika AI tidak mengikuti aturan SenopaTEA?"

File `.agents/` hanya bersifat **instruksi**, bukan enforcement otomatis. Efektivitasnya bergantung pada AI assistant yang membacanya. Namun, karena file ini ada di root project, kebanyakan Agentic IDE (Cursor, Windsurf, dll) akan otomatis membacanya sebagai konteks.

### "Apakah SenopaTEA menambah ukuran proyek?"

**Minimal.** Total 9 file markdown = ~1.145 baris teks. Tidak ada dependensi, tidak ada runtime code, tidak ada impact ke performance aplikasi.

### "Kapan harus update context.md?"

Setiap kali ada:
- Model/tabel baru
- Modul baru
- Perubahan RBAC (role/permission)
- Perubahan tech stack
- Konvensi baru

---

## Lampiran: Perbandingan SenopaTEA Original vs Adaptasi Lawangsewu

| Aspek | SenopaTEA Original | Adaptasi Lawangsewu |
|---|---|---|
| **Target** | Proyek baru dari nol | Proyek existing yang sedang berkembang |
| **`app_build/`** | Staging directory untuk kode baru | ❌ Dihapus — kode langsung ke struktur existing |
| **`production_artifacts/`** | Penyimpan spec | → `docs/` (sudah ada 13 dokumen) |
| **Tech stack** | Polyglot (PHP, Python, JS, HTML) | 🔒 Locked: Laravel 13 + Vue 3 + Tailwind |
| **`@devops` deploy** | Generic: detect stack → install → serve | Spesifik: Laravel migration, Reverb, Nginx, SSL |
| **Workflow** | Hanya `/startcycle` | + `/hotfix` (quick fix) + `/audit` (health check) |
| **Konteks** | Tidak ada | + `context.md` (194 baris arsitektur lengkap) |
| **Security audit** | Generic scan | 5-layer: syntax → logic → security → test → performance |
| **Bahasa** | English | Bahasa Indonesia |
| **Approval prompt** | "Do you approve?" | "Tuan Muda, mohon persetujuan" |
