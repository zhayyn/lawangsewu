# Arsitektur SSO & RBAC di Lawangsewu

> **Tanggal:** 15 April 2026  
> **Kategori:** Arsitektur Keamanan  
> **Status:** Aktif — Berlaku untuk Lawangsewu V2

---

## Daftar Isi

1. [Pendahuluan](#1-pendahuluan)
2. [Apa Itu SSO?](#2-apa-itu-sso-single-sign-on)
3. [Apa Itu RBAC?](#3-apa-itu-rbac-role-based-access-control)
4. [Perbedaan SSO vs Login Google Biasa](#4-perbedaan-sso-vs-login-google-biasa)
5. [Perbedaan SSO vs RBAC](#5-perbedaan-sso-vs-rbac)
6. [Bagaimana SSO & RBAC Bekerja Bersama di Lawangsewu](#6-bagaimana-sso--rbac-bekerja-bersama-di-lawangsewu)
7. [Alur Teknis SSO Google](#7-alur-teknis-sso-google)
8. [Alur Teknis RBAC](#8-alur-teknis-rbac)
9. [Referensi Kode](#9-referensi-kode)

---

## 1. Pendahuluan

Lawangsewu adalah portal digital terpadu untuk Pengadilan Agama Semarang yang menggabungkan beberapa modul dalam satu ekosistem:

| Modul | Fungsi |
|---|---|
| **PTSP** | Antrian Pelayanan Terpadu Satu Pintu |
| **Sidang** | Antrian Persidangan |
| **Pendopo** | Buku Tamu Digital |
| **Chat** | Komunikasi Internal |
| **CCTV** | Monitoring Keamanan |
| **Admin Panel** | Manajemen User & Sistem |

Untuk mengamankan dan mengelola akses ke seluruh modul ini, Lawangsewu menggunakan dua sistem yang **saling melengkapi**:

- **SSO (Single Sign-On)** → Mengelola *siapa kamu* (autentikasi)
- **RBAC (Role-Based Access Control)** → Mengelola *kamu boleh apa* (otorisasi)

---

## 2. Apa Itu SSO (Single Sign-On)?

### Definisi

**SSO (Single Sign-On)** adalah mekanisme autentikasi yang memungkinkan pengguna **login sekali** dan mendapat akses ke **banyak aplikasi/modul** tanpa perlu login ulang di setiap aplikasi.

### Analogi: Gelang Festival 🎫

> Bayangkan kamu datang ke sebuah **festival musik** yang punya banyak panggung:
> 
> - 🎸 Panggung Rock
> - 🎷 Panggung Jazz
> - 🎵 Panggung Dangdut
> - 🍔 Area Food Court
> 
> **Tanpa SSO (tiket per panggung):**
> - Kamu beli tiket di loket Panggung Rock → masuk ✅
> - Pindah ke Panggung Jazz → antri beli tiket **lagi** → masuk ✅
> - Mau makan → antri beli tiket Food Court **lagi** → masuk ✅
> - **Capek, ribet, buang waktu.**
> 
> **Dengan SSO (gelang festival):**
> - Kamu datang ke gerbang utama → tunjukkan KTP → dapat **satu gelang** 🎫
> - Masuk Panggung Rock → tunjukkan gelang → ✅
> - Pindah ke Panggung Jazz → tunjukkan gelang → ✅
> - Mau makan → tunjukkan gelang → ✅
> - **Satu kali proses, akses ke semua area.**

### Poin Penting SSO

- SSO **BUKAN** tentang mengatur siapa boleh masuk ke area mana
- SSO **HANYA** tentang: *"Berapa kali kamu harus membuktikan identitasmu?"*
- Jawabannya: **sekali saja**

---

## 3. Apa Itu RBAC (Role-Based Access Control)?

### Definisi

**RBAC (Role-Based Access Control)** adalah sistem otorisasi yang mengatur **hak akses berdasarkan peran (role)**. Setiap role memiliki kumpulan permission yang menentukan apa saja yang boleh dilakukan.

### Analogi: Tanda Warna Gelang 🔴🟡🟢

> Lanjutkan analogi festival di atas. Sekarang gelang-gelang itu punya **warna berbeda**:
> 
> - 🟢 **Gelang Hijau (Viewer/Pengunjung)** → Boleh masuk semua panggung, tapi hanya menonton. Tidak boleh naik ke backstage.
> - 🟡 **Gelang Kuning (Operator/Crew)** → Boleh masuk panggung + backstage di area tugasnya. Tidak boleh ubah jadwal acara.
> - 🔴 **Gelang Merah (Admin/Panitia)** → Boleh akses semua area + ubah jadwal + kelola crew.
> - 👑 **Gelang Emas (Superadmin/Ketua Panitia)** → Akses tanpa batas ke seluruh festival.
> 
> Jadi **warna gelang = role**, dan setiap warna menentukan **kamu boleh ngapain**.

### Hierarki Role di Lawangsewu

```
👑 Superadmin (gelang emas)
   └── Akses penuh, tanpa batasan
       │
🔴 Admin (gelang merah)
   └── Kelola user, modul, konfigurasi
       │
🟡 Operator (gelang kuning)
   └── Operasikan modul tertentu (panggil antrian, kelola sidang)
       │
🟢 Viewer (gelang hijau)
   └── Lihat data, akses terbatas
```

### Sistem Permission per Modul

Setiap role memiliki daftar permission yang di-mapping ke modul tertentu:

| Permission | Module | Viewer | Operator | Admin |
|---|---|---|---|---|
| `dashboard.view` | Dashboard | ✅ | ✅ | ✅ |
| `ptsp.view` | PTSP | ✅ | ✅ | ✅ |
| `ptsp.call` | PTSP | ❌ | ✅ | ✅ |
| `sidang.view` | Sidang | ✅ | ✅ | ✅ |
| `sidang.call` | Sidang | ❌ | ✅ | ✅ |
| `users.manage` | Admin | ❌ | ❌ | ✅ |
| `cctv.view` | CCTV | ✅ | ✅ | ✅ |

> **Superadmin** otomatis memiliki **semua permission** tanpa perlu di-mapping.

---

## 4. Perbedaan SSO vs Login Google Biasa

### Analogi: Toko vs Kompleks Perkantoran

| Aspek | Login Google Biasa 🏪 | SSO Google di Lawangsewu 🏢 |
|---|---|---|
| **Analogi** | Toko yang pintunya terbuka. Siapapun punya KTP Google bisa masuk. | Kompleks perkantoran dengan gerbang terpusat dan daftar tamu. |
| **Siapa boleh masuk?** | Semua pemilik akun Google | Hanya email yang terdaftar di allowlist admin |
| **Perlu persetujuan admin?** | Tidak | Ya — ada mekanisme pending approval |
| **Kontrol terpusat?** | Tidak — tiap app kelola sendiri | Ya — satu `users` table untuk semua modul |
| **Nonaktifkan user?** | Harus dilakukan per app | Cukup 1 klik, tertutup dari semua modul |
| **Password?** | User kelola sendiri | Tidak perlu — OAuth token dari Google |
| **Data profil?** | Bisa berbeda di tiap app | Konsisten di semua modul |

### Yang Google Lakukan vs Yang Lawangsewu Lakukan

```
┌─────────────────────────────────────┐
│            GOOGLE                   │
│   "Saya jamin orang ini benar      │
│    pemilik email ini"               │
│   (Identity Provider)               │
└──────────────┬──────────────────────┘
               │ OAuth Token
               ▼
┌─────────────────────────────────────┐
│          LAWANGSEWU                 │
│   "OK, saya percaya Google.         │
│    Sekarang saya yang putuskan:     │
│    - Apakah email ini ada di        │
│      daftar akses? (allowlist)      │
│    - Sudah diaktifkan admin?        │
│    - Role-nya apa?                  │
│    - Permission apa yang dia punya?"│
│   (Service Provider)                │
└─────────────────────────────────────┘
```

**Intinya:** Google = tukang verifikasi KTP. Lawangsewu = yang pegang keputusan siapa boleh masuk dan boleh ngapain.

---

## 5. Perbedaan SSO vs RBAC

Ini adalah pertanyaan yang paling sering membingungkan. Jawabannya sederhana:

| | SSO | RBAC |
|---|---|---|
| **Pertanyaan yang dijawab** | *"Siapa kamu?"* | *"Kamu boleh apa?"* |
| **Kategori** | Autentikasi (Authentication) | Otorisasi (Authorization) |
| **Fokus** | Login sekali, akses banyak app | Atur izin berdasarkan role |
| **Bisa berdiri sendiri?** | Ya, tanpa RBAC | Ya, tanpa SSO |
| **Saling menggantikan?** | **Tidak** — keduanya saling melengkapi | **Tidak** — keduanya saling melengkapi |

### Analogi Lengkap: Airport 🛫

> **SSO = Paspor & Boarding Pass**
> - Kamu tunjukkan paspor **sekali** di konter check-in
> - Kamu dapat boarding pass yang berlaku di:
>   - Gate keberangkatan ✅
>   - Imigrasi ✅  
>   - Lounge ✅ (tergantung kelas)
>   - Pesawat ✅
> - **Tidak perlu tunjukkan paspor berulang-ulang** di setiap titik
> 
> **RBAC = Kelas Tiket**
> - 🟢 **Ekonomi:** Duduk di kursi biasa, makan standar
> - 🟡 **Bisnis:** Kursi lebar, akses lounge, prioritas boarding
> - 🔴 **First Class:** Suite pribadi, lounge eksklusif, layanan penuh
> - 👑 **Pilot/Crew:** Akses ke kokpit dan seluruh pesawat
>
> **SSO memastikan kamu tidak perlu ulang proses check-in di setiap titik.**  
> **RBAC memastikan kamu hanya masuk area yang sesuai kelasmu.**

---

## 6. Bagaimana SSO & RBAC Bekerja Bersama di Lawangsewu

### Diagram Alur

```
  Pengguna membuka Lawangsewu
            │
            ▼
  ┌─────────────────────┐
  │    Halaman Login     │
  │  [Login dengan Google]│
  └──────────┬──────────┘
             │
             ▼
  ┌─────────────────────┐
  │  Google OAuth 2.0    │ ◄── SSO: Verifikasi identitas
  │  "Siapa kamu?"       │
  └──────────┬──────────┘
             │ email, google_id, name, avatar
             ▼
  ┌─────────────────────┐
  │  Cek Allowlist       │ ◄── SSO: Boleh masuk gerbang?
  │  google_access_      │
  │  allowlist            │
  └──────┬────────┬─────┘
         │        │
    Tidak ada    Ada
         │        │
         ▼        ▼
  ┌──────────┐ ┌─────────────────┐
  │ DITOLAK  │ │ Cek Status User │
  │ "Email   │ │ is_active?      │
  │ belum    │ └──────┬────┬─────┘
  │ terdaftar│        │    │
  └──────────┘   false│    │true
                      │    │
                      ▼    ▼
              ┌──────────┐ ┌───────────────────┐
              │ PENDING  │ │  LOGIN BERHASIL!  │ ◄── SSO selesai
              │ "Tunggu  │ │  Session dibuat   │
              │ approval"│ │  (gelang 🎫)       │
              └──────────┘ └────────┬──────────┘
                                    │
                          ┌─────────▼─────────┐
                          │    RBAC Engine     │ ◄── RBAC mulai bekerja
                          │  "Boleh apa?"      │
                          └───┬───┬───┬───┬───┘
                              │   │   │   │
                     ┌────────┘   │   │   └────────┐
                     ▼            ▼   ▼            ▼
                  ┌──────┐  ┌───────┐ ┌──────┐ ┌──────┐
                  │ PTSP │  │Sidang │ │ Chat │ │Admin │
                  └──────┘  └───────┘ └──────┘ └──────┘

                  Akses ke modul ditentukan oleh role & permission
```

### Urutan Kerja

| Langkah | Sistem | Apa yang terjadi |
|---|---|---|
| 1 | **SSO** | User klik "Login dengan Google" |
| 2 | **SSO** | Google verifikasi: "Email ini valid, ini orangnya" |
| 3 | **SSO** | Lawangsewu cek allowlist: "Email ini boleh masuk?" |
| 4 | **SSO** | Lawangsewu cek status: "Akun sudah diaktifkan admin?" |
| 5 | **SSO** | Session dibuat → user login ✅ |
| 6 | **RBAC** | Setiap kali user akses modul → cek role & permission |
| 7 | **RBAC** | Role `viewer`? Hanya bisa lihat. Role `admin`? Bisa kelola. |

---

## 7. Alur Teknis SSO Google

### Flow OAuth 2.0

```
Browser                    Lawangsewu                     Google
  │                            │                              │
  │  GET /auth/google          │                              │
  │ ─────────────────────────► │                              │
  │                            │  Redirect ke accounts.google │
  │ ◄────────────────────────  │  + client_id + scopes        │
  │                            │                              │
  │  User login di Google      │                              │
  │ ──────────────────────────────────────────────────────────►│
  │                            │                              │
  │                            │  POST callback + auth code   │
  │  ◄────────────────────────────────────────────────────────│
  │                            │                              │
  │  POST /auth/google/callback│                              │
  │ ─────────────────────────► │                              │
  │                            │  Exchange code for token     │
  │                            │ ────────────────────────────►│
  │                            │                              │
  │                            │  Return user info            │
  │                            │ ◄────────────────────────────│
  │                            │                              │
  │                            │  completeGoogleLogin()       │
  │                            │  - Cek allowlist              │
  │                            │  - Create/update user        │
  │                            │  - Cek is_active             │
  │                            │  - Auth::login()             │
  │                            │                              │
  │  Redirect ke Dashboard     │                              │
  │ ◄─────────────────────────│                              │
```

### Mekanisme Keamanan SSO

| Fitur | Penjelasan | File |
|---|---|---|
| **Allowlist** | Hanya email terdaftar yang boleh masuk | `GoogleAccessAllowlist` model |
| **Pending Approval** | User baru bisa ditahan sampai admin menyetujui | `is_active` field di `users` |
| **Auto-activate** | Admin bisa set email tertentu langsung aktif | `auto_activate` di allowlist |
| **Superadmin Hardcode** | Email di env `SUPERADMIN_EMAIL` otomatis jadi superadmin | `config('auth.super_admin_email')` |
| **Stateless Fallback** | Jika state session corrupt, retry tanpa state (HTTPS edge case) | `InvalidStateException` catch |
| **Middleware Guard** | User yang sudah login tapi di-nonaktifkan akan di-kick otomatis | `EnsureActiveUser` middleware |

---

## 8. Alur Teknis RBAC

### Komponen RBAC

```
┌─────────────────────────────────────────────────────┐
│                    RBAC System                       │
│                                                      │
│  ┌──────────┐    ┌─────────────────┐    ┌──────────┐│
│  │  users    │    │ role_permissions │    │permissions││
│  │          │    │                  │    │          ││
│  │ role ────┼───►│ role            │    │ id       ││
│  │          │    │ permission_id ──┼───►│ name     ││
│  │          │    │                  │    │ module   ││
│  └──────────┘    └─────────────────┘    └──────────┘│
│                                                      │
│  Contoh:                                             │
│  User "Budi" (role: operator)                        │
│    ├── role_permissions: operator → ptsp.view         │
│    ├── role_permissions: operator → ptsp.call          │
│    ├── role_permissions: operator → sidang.view        │
│    └── role_permissions: operator → sidang.call        │
└─────────────────────────────────────────────────────┘
```

### Cara Pengecekan Permission di Kode

```php
// Cek apakah user punya permission tertentu
$user->hasPermission('ptsp.call');          // true/false

// Cek apakah user punya salah satu dari banyak permission
$user->hasAnyPermission(['ptsp.call', 'sidang.call']); // true/false

// Cek apakah user punya semua permission
$user->hasAllPermissions(['ptsp.call', 'ptsp.view']);   // true/false

// Cek level role (hierarki)
$user->isAtLeast('operator');   // true jika role >= operator
$user->isAtLeast('admin');      // true jika role >= admin

// Cek role spesifik
$user->hasRole('admin');        // true/false
$user->hasAnyRole(['admin', 'operator']); // true/false

// Superadmin selalu return true untuk semua pengecekan
$user->isSuperAdmin();          // true jika is_superadmin atau email match
```

### Hierarki Role (Level)

```php
// Dari app/Core/Traits/HasRolesAndPermissions.php
$hierarchy = [
    'viewer'     => 1,  // Level terendah
    'operator'   => 2,
    'admin'      => 3,
    'superadmin' => 4,  // Level tertinggi
];
```

Fungsi `isAtLeast()` membandingkan level numerik, sehingga:
- `operator->isAtLeast('viewer')` = ✅ (2 >= 1)
- `viewer->isAtLeast('operator')` = ❌ (1 < 2)
- `superadmin->isAtLeast('admin')` = ✅ (selalu true, bypass)

---

## 9. Referensi Kode

### File Terkait SSO

| File | Fungsi |
|---|---|
| `app/Http/Controllers/Auth/GoogleController.php` | Controller utama Google OAuth (redirect, callback, completeLogin) |
| `app/Models/GoogleAccessAllowlist.php` | Model daftar email yang diizinkan login |
| `app/Services/GoogleIdTokenVerifier.php` | Verifikasi ID Token Google (credential flow) |
| `app/Http/Middleware/EnsureActiveUser.php` | Middleware yang kick user non-aktif |
| `routes/auth.php` | Route definitions untuk OAuth flow |
| `config/auth.php` | Konfigurasi `super_admin_email` |
| `config/services.php` | Konfigurasi Google OAuth (client_id, client_secret, redirect) |

### File Terkait RBAC

| File | Fungsi |
|---|---|
| `app/Core/Traits/HasRolesAndPermissions.php` | Trait yang di-use oleh User model — berisi semua fungsi pengecekan permission |
| `app/Core/Models/Permission.php` | Model permission (name, description, module) |
| `app/Models/RolePermission.php` | Pivot model yang menghubungkan role ke permission |
| `app/Models/User.php` | Model user dengan field `role`, `is_active`, `is_superadmin` |
| `app/Console/Commands/VerifyRBAC.php` | Command artisan `rbac:verify` untuk validasi integritas sistem RBAC |
| `app/Http/Controllers/Admin/UserAccessController.php` | Controller untuk kelola akses user oleh admin |

### Perintah Artisan

```bash
# Verifikasi integritas sistem RBAC
php artisan rbac:verify

# Verifikasi dengan auto-fix
php artisan rbac:verify --fix
```

---

## Lampiran: Keuntungan Arsitektur SSO + RBAC

### Mengapa Tidak Cukup Hanya Salah Satu?

| Skenario | Masalah |
|---|---|
| **SSO tanpa RBAC** | Semua user punya akses yang sama — tidak ada pembatasan per modul. Petugas PTSP bisa mengakses data sidang, tamu bisa mengubah konfigurasi. |
| **RBAC tanpa SSO** | Setiap modul/aplikasi punya login sendiri. User harus login berkali-kali. Admin harus kelola user di banyak tempat. Nonaktifkan pegawai harus di semua sistem. |
| **SSO + RBAC** ✅ | Login sekali, akses terkontrol. Satu titik kelola identitas. Satu titik kelola izin. Aman dan efisien. |

### Ringkasan dalam Satu Kalimat

> **SSO memastikan kamu hanya perlu mengetuk pintu sekali. RBAC memastikan kamu hanya masuk ruangan yang sesuai dengan warna gelangmu.**
