# lawangsewu

Project for lawangsewu

## Deploy lumpiapasar-s9

Gunakan script berikut untuk sinkronisasi source ke folder active:

- Cek perubahan (aman): `./scripts/deploy-lumpiapasar-s9.sh`
- Eksekusi deploy: `./scripts/deploy-lumpiapasar-s9.sh --apply`

## Struktur endpoint Lawangsewu

- URL publik tetap sama (contoh: `/info-persidangan`, `/monitor-antrian-sidang`)
- Implementasi internal sekarang dirapikan berdasarkan tipe di `widgets/views/html/public/`, `widgets/views/php/public/`, `widgets/views/php/api/`, `widgets/views/php/system/`, `widgets/views/data/`, dan `widgets/views/config/`
- Akses URL langsung ke `widgets/views/*` diblokir (`403`)
- Untuk eksekusi CLI generator: `php widgets/views/php/system/generate_slide.php`
- Dokumentasi pengumuman dan berita pengadilan: `README-PA-SEMARANG-PENGUMUMAN.md`
- Katalog dokumentasi terpusat: `Walkthrough-DBPrakom/`
- Halaman dokumentasi publik: `/walkthrough`
- Direktori link widget final dan snippet iframe: `/daftar-widget`
- developed by dbprakom

## Peta sistem internal

- Peta sistem internal Lawangsewu: `SYSTEMS-LAWANGSEWU.md`
- Runtime WhatsApp API terisolasi: `wa-caraka/`
- Wrapper web untuk dashboard admin WA Caraka: `wa-caraka-admin/`
- Dashboard admin CI4 WA Caraka: `wa-caraka/dashboard-ci4-admin/`

## Smoke WA Caraka

- Runner pasca-deploy: `bash scripts/post-deploy-wa-caraka-smoke.sh`
- Installer cron smoke harian: `bash scripts/setup-wa-caraka-smoke-cron.sh`
- Smoke host publik/staging: `bash scripts/smoke-login-process-production.sh`
- Report terbaru: `logs/post-deploy-smoke/wa-caraka-post-deploy-latest.log`

## OpenAPI WA Caraka

- Spesifikasi: `wa-caraka/docs/openapi.yaml`
- Swagger UI statis: `wa-caraka/docs/swagger-ui.html`
- Swagger UI internal admin: `wa-caraka-admin/wa/docs/swagger`

## Remote Access Security

- Dokumen arsitektur, alasan, dan SOP keamanan remote access Server 9: `REMOTE-ACCESS-SECURITY-SERVER9.md`
- Versi 1 halaman (bahasa sederhana + perumpamaan): `REMOTE-ACCESS-SECURITY-SERVER9-SIMPLE.md`
- Versi 1 halaman PDF-friendly (briefing pimpinan): `REMOTE-ACCESS-SECURITY-SERVER9-ONEPAGE.md`
- Versi HTML siap print ke PDF (A4): `REMOTE-ACCESS-SECURITY-SERVER9-ONEPAGE.html`
- Versi resmi (header instansi + kolom persetujuan): `REMOTE-ACCESS-SECURITY-SERVER9-ONEPAGE-OFFICIAL.html`

<!-- autocommit test -->
<!-- autocommit test 2 -->
<!-- autocommit test 3 -->
