# Audit WA Caraka (2026-03-07)

## Scope
- Login/auth flow (CI4 admin)
- Dashboard + WA Console process actions
- Report pipeline (intent report + knowledge alert)
- Backup and migration readiness

## Findings

### High
1. Host-mismatch risk on redirect URL (fixed)
- Dampak: setelah submit action bisa terlempar ke host/domain yang tidak sesuai (potensi 404/no-response pada deployment proxy/domain campuran).
- Lokasi: `wa-caraka/dashboard-ci4-admin/app/Controllers/AuthController.php`, `wa-caraka/dashboard-ci4-admin/app/Controllers/DashboardController.php`, `wa-caraka/dashboard-ci4-admin/app/Controllers/MessageController.php`, `wa-caraka/dashboard-ci4-admin/app/Controllers/DeviceController.php`.
- Perbaikan: redirect dibuat path-based.

2. Account enumeration melalui pesan login berbeda (fixed)
- Dampak: attacker bisa membedakan akun valid/nonaktif vs password salah.
- Lokasi: `wa-caraka/dashboard-ci4-admin/app/Controllers/AuthController.php`.
- Perbaikan: pesan gagal login diseragamkan menjadi "Username atau password salah.".

### Medium
3. False-positive pada audit endpoint sensitif (fixed)
- Dampak: script audit menandai FAIL meski endpoint tidak dapat diakses (404).
- Lokasi: `scripts/security-audit.sh`.
- Perbaikan: validasi endpoint sensitif menerima `403` atau `404`.

4. Parsing `.env` raw berisiko saat nilai dikutip (fixed)
- Dampak: script report/alert dapat gagal jika nilai `.env` ditulis dengan `'...'` atau `"..."`.
- Lokasi: `wa-caraka/scripts/generate-weekly-intent-report.sh`, `wa-caraka/scripts/check-knowledge-alert.sh`.
- Perbaikan: parser env menormalkan dan menghapus quote pembungkus.

### Operational Gaps
5. Belum ada verifikasi restore backup terenkripsi (fixed)
- Dampak: backup ada, tapi validitas restore tidak teruji rutin.
- Perbaikan: tambah `scripts/verify-backup.sh`.

6. Belum ada paket migrasi standar GitHub -> server (fixed)
- Dampak: migrasi lintas server rawan manual error.
- Perbaikan: tambah `scripts/prepare-github-migration.sh` + `MIGRASI-GITHUB-SERVER.md`.

## Test Evidence
- `bash scripts/security-audit.sh http://127.0.0.1:8792` -> PASS.
- `bash wa-caraka/scripts/generate-weekly-intent-report.sh 1 5` -> report berhasil dibuat.
- `bash scripts/prepare-github-migration.sh http://127.0.0.1:8792` -> artefak migrasi berhasil dibuat di `releases/migration/`.

## Remaining Recommendations
1. Aktifkan CSP dan secure headers di production (min. `script-src`, `frame-ancestors`, `upgrade-insecure-requests`).
2. Evaluasi `forceGlobalSecureRequests` + reverse proxy trust config agar konsisten HTTPS end-to-end.
3. Tambahkan smoke test authenticated berbasis host produksi (atau staging) untuk menghindari mismatch cookie domain saat uji lokal.
4. Jalankan drill restore bulanan: decrypt -> verify -> bootstrap service di host uji.
