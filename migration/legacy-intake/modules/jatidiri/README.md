# Jatidiri Laravel 12 (Terintegrasi Lawangsewu)

Project ini adalah modernisasi aplikasi Jatidiri ke Laravel 12, terhubung ke portal Lawangsewu melalui signed SSO.

## URL Akses

- Domain lokal: `http://jatidiri.lawangsewu.local`
- Endpoint SSO consume: `/sso/consume`
- Dashboard: `/dashboard`
- Direktori Pegawai: `/employees`
- Layanan Kepegawaian: `/services`

## Integrasi SSO

Konfigurasi ada di file environment:

- `LAWANGSEWU_SSO_MODE=signed`
- `LAWANGSEWU_SSO_SHARED_SECRET=<harus sama dengan gateway>`
- `LAWANGSEWU_SSO_TTL_SECONDS=300`

Gateway launcher dikonfigurasi dari:

- `gateway/.env` pada `GATEWAY_JATIDIRI_BASE_URL`

## Sinkronisasi Pegawai

Sumber yang didukung:

1. `sikep` (API resmi SIKEP)
2. `sikep-portal` (login admin SIKEP + endpoint export data)

Command:

```bash
php artisan employees:sync --source=sikep
php artisan employees:sync --source=sikep-portal
```

## SIKEP Tanpa API Token

Bisa dilakukan jika akun admin SIKEP Anda memiliki akses halaman export data pegawai.

Isi variabel ini di `.env`:

- `SIKEP_PORTAL_LOGIN_URL`
- `SIKEP_PORTAL_EMPLOYEE_EXPORT_URL`
- `SIKEP_PORTAL_USERNAME`
- `SIKEP_PORTAL_PASSWORD`
- `SIKEP_PORTAL_USERNAME_FIELD` (default `username`)
- `SIKEP_PORTAL_PASSWORD_FIELD` (default `password`)
- `SIKEP_PORTAL_CSRF_FIELD` (default `_csrf`)

Catatan: mode ini bergantung pada struktur form login dan halaman export SIKEP. Jika pihak SIKEP mengubah UI/field, konfigurasi perlu disesuaikan.

## Layanan Native

Layanan yang aktif sebagai workflow native Jatidiri:

- Direktori Pegawai
- Pengajuan Cuti
- Izin Belajar
- Surat Tugas

Format naskah dan template dokumen dapat disempurnakan berikutnya tanpa mengubah fondasi data.
