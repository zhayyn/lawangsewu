# Legacy Intake Mapping to Lawangsewu Blueprint

Tanggal audit: 2026-04-05
Sumber: /var/www/lawangsewu_legacy
Target staging: /var/www/lawangsewu/migration/legacy-intake

## Status Ringkas

- Lawangsewu Core aktif sudah berada di /var/www/lawangsewu.
- Komponen legacy yang relevan blueprint sudah disalin ke staging intake.
- Komponen non-relevan (log, archive, release snapshot, backup session, node_modules, vendor) tidak ikut dipindah.

## Yang Sudah Dipindah (Staging Aman)

1. Modul Jatidiri
   - Sumber: /var/www/lawangsewu_legacy/projects/jatidiri
   - Tujuan: /var/www/lawangsewu/migration/legacy-intake/modules/jatidiri
   - Catatan: runtime artifact dikecualikan (vendor, node_modules, storage, .env).

2. Modul Widgets (basis SIPP Hub/public widget)
   - Sumber: /var/www/lawangsewu_legacy/widgets
   - Tujuan: /var/www/lawangsewu/migration/legacy-intake/modules/widgets

3. Komponen Integration Gateway
   - Sumber: /var/www/lawangsewu_legacy/gateway
   - Tujuan: /var/www/lawangsewu/migration/legacy-intake/integration/gateway

4. Dokumen Legacy
   - Sumber: /var/www/lawangsewu_legacy/docs
   - Tujuan: /var/www/lawangsewu/migration/legacy-intake/docs

## Pemetaan ke Blueprint

1. Pintu Jatidiri (Kepegawaian)
   - Kandidat utama: modules/jatidiri
   - Tindak lanjut: ekstrak domain model pegawai + layanan ke modul Lawangsewu (Inertia page + service layer).

2. SIPP Hub & Data
   - Kandidat utama: modules/widgets
   - Tindak lanjut: pindahkan endpoint/widget publik ke route-controller Laravel dan rapikan asset pipeline Vite.

3. Integration Bus
   - Kandidat utama: integration/gateway
   - Tindak lanjut: tentukan kontrak API internal yang dipakai Core (SSO launch, sync trigger, webhook).

## Yang Tidak Dipindah (Sengaja Ditahan)

- /var/www/lawangsewu_legacy/logs
- /var/www/lawangsewu_legacy/archive
- /var/www/lawangsewu_legacy/releases
- /var/www/lawangsewu_legacy/lawangsewu_backup
- /var/www/lawangsewu_legacy/wa-caraka (tetap sibling terpisah)
- dependency cache besar (vendor/node_modules)

## Rekomendasi Rapikan Lanjutan di Legacy

1. Pindahkan logs, archive, releases, backup ke satu folder arsip jangka panjang.
2. Sisakan hanya folder sumber yang masih bernilai migrasi.
3. Tambahkan README status per folder (retain/migrate/deprecate).

## Kesimpulan

Bisa dirapikan dan dipindahkan ke Lawangsewu sesuai blueprint, dan tahap intake aman sudah selesai.
Langkah berikutnya adalah integrasi per modul (Jatidiri, Widgets/SIPP Hub, Integration Gateway) ke kode utama Laravel.
