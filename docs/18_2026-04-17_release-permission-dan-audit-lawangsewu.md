# Rilis Lawangsewu 2026-04-17: Permission Matrix & Audit Trail

Dokumen ini merangkum perubahan versi terbaru Lawangsewu terkait penguatan kontrol akses modul, manajemen permission yang dapat dioperasikan dari dashboard admin, dan audit trail perubahan permission.

## Ringkasan Perubahan

### 1. Feature Permission Matrix

Ditambahkan mekanisme permission berbasis fitur dengan dua level:
- Role-level default permission
- User-level override permission (prioritas tertinggi)

Sumber definisi fitur berada di `config/features.php` dan dibangun ke tabel `feature_permissions`.

### 2. Enforcement di Route Admin

Middleware permission kini digunakan langsung pada route admin per fitur:
- `admin.users`
- `admin.users.allowlist`
- `admin.system-monitor`
- `admin.pendopo`
- `admin.cctv`
- `admin.wacaraka`

Efeknya: akses tidak lagi hanya bergantung role global, tetapi juga status permission feature yang aktif saat runtime.

### 3. Panel Permission di Admin Users

Halaman Admin Users kini memiliki:
- Matrix toggle permission per role
- Override permission per user (`Allow`, `Deny`, `Reset`)

Ini memungkinkan superadmin mengelola akses modul secara granular tanpa edit manual database.

### 4. Audit Trail Permission

Setiap aksi perubahan permission kini terekam ke tabel `permission_audit_logs`:
- aktor (siapa yang mengubah)
- target user/role
- feature key
- status sebelum/sesudah
- action type
- ip address dan user agent
- timestamp

Halaman Admin Users juga menampilkan timeline audit permission terbaru untuk monitoring operasional.

## Migrasi Database

Pastikan migrasi berikut sudah diterapkan:
- `2026_04_17_105124_create_feature_permissions_table`
- `2026_04_17_230000_create_permission_audit_logs_table`

Perintah:

```bash
php artisan migrate --force
```

## Testing Coverage

Pengujian utama yang mencakup perubahan ini:
- `tests/Feature/Admin/UserAccessApprovalTest.php`
- `tests/Feature/Admin/FeaturePermissionUiAndAuditTest.php`

Perintah:

```bash
php artisan test tests/Feature/Admin/UserAccessApprovalTest.php tests/Feature/Admin/FeaturePermissionUiAndAuditTest.php
```

## Catatan Operasional WA Caraka (`wa-runtime`)

Folder `wa-runtime` adalah runtime Node.js untuk integrasi WA Caraka, termasuk:
- server runtime (`server.mjs`)
- dependency node (`node_modules`)
- state sesi WhatsApp (`baileys_auth_info`)
- log runtime (`server.log`)

Penting untuk operasi WA Caraka, tetapi file sesi dan artefak runtime bersifat sensitif/volatile. Secara praktik operasional, yang disarankan untuk version control hanya kode runtime dan konfigurasi yang memang diperlukan, bukan state sesi dinamis.

## Status Rilis

Rilis dinyatakan siap untuk UAT/operasional internal setelah:
- migrasi berhasil,
- test suite admin permission lulus,
- panel permission + audit timeline terverifikasi di UI.
