# lawangsewu

Project for lawangsewu

## Deploy lumpiapasar-s9

Gunakan script berikut untuk sinkronisasi source ke folder active:

- Cek perubahan (aman): `./scripts/deploy-lumpiapasar-s9.sh`
- Eksekusi deploy: `./scripts/deploy-lumpiapasar-s9.sh --apply`

## Struktur endpoint Lawangsewu

- URL publik tetap sama (contoh: `/info-persidangan`, `/monitor-antrian-sidang`)
- Implementasi file sekarang dipusatkan di `widgets/views/`
- Akses URL langsung ke `widgets/views/*` diblokir (`403`)
- Untuk eksekusi CLI generator: `php widgets/views/generate_slide.php`

## Smoke WA Caraka

- Runner pasca-deploy: `bash scripts/post-deploy-wa-caraka-smoke.sh`
- Installer cron smoke harian: `bash scripts/setup-wa-caraka-smoke-cron.sh`
- Smoke host publik/staging: `bash scripts/smoke-login-process-production.sh`
- Report terbaru: `logs/post-deploy-smoke/wa-caraka-post-deploy-latest.log`

## OpenAPI WA Caraka

- Spesifikasi: `wa-caraka/docs/openapi.yaml`
- Swagger UI statis: `wa-caraka/docs/swagger-ui.html`
- Swagger UI internal admin: `wa-caraka-admin/wa/docs/swagger`

<!-- autocommit test -->
<!-- autocommit test 2 -->
<!-- autocommit test 3 -->
