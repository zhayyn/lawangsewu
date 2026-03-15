# Portal Shadow Change

Perubahan ini menyiapkan shadow terkontrol untuk route `portal` dan `portal/launch`.

## Implementasi

- `.htaccess` sekarang mengenali flag `runtime-flags/portal-shadow-on.flag`
- jika flag aktif, route `portal` dan `portal/launch` diarahkan ke `portal-shadow-proxy.php`
- proxy memanggil shell staging `projects/lawangsewu-core-ci4/public/index.php`

## Guardrail

- default tetap `off`
- akses langsung ke `portal-shadow-proxy.php` diblok oleh rewrite rule
- rollback cukup dengan menghapus flag atau menjalankan `scripts/disable-portal-shadow.sh`

## Status Awal

- readiness `portal` dan `portal-launch` sudah `ready-for-shadow-auth`
- smoke anon dan sesi sintetis staging sudah tervalidasi