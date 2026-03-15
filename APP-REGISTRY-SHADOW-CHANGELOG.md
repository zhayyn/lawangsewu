# App Registry Shadow Change

Perubahan ini menyiapkan shadow terkontrol untuk route `app-registry` dan `app-registry/launch`.

## Implementasi

- `.htaccess` sekarang mengenali flag `runtime-flags/app-registry-shadow-on.flag`
- jika flag aktif, route `app-registry` dan `app-registry/launch` diarahkan ke `app-registry-shadow-proxy.php`
- proxy memanggil shell staging `projects/lawangsewu-core-ci4/public/index.php`

## Guardrail

- default tetap `off`
- akses langsung ke `app-registry-shadow-proxy.php` diblok oleh rewrite rule
- rollback cukup dengan menghapus flag atau menjalankan `scripts/disable-app-registry-shadow.sh`

## Status Awal

- readiness `app-registry` sudah `ready-for-shadow`
- smoke staging untuk index dan launcher sudah tervalidasi