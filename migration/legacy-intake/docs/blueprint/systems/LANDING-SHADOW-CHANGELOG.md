# Landing Shadow Change

Perubahan ini menyiapkan shadow terbatas untuk route landing `/`.

## Implementasi

- `.htaccess` sekarang mengenali flag `runtime-flags/landing-shadow-on.flag`
- jika flag aktif, route `/` diarahkan ke `landing-shadow-proxy.php`
- proxy memanggil shell staging `projects/lawangsewu-core-ci4/public/index.php`
- proxy mengunci `REQUEST_URI` ke `/` agar perilaku GET dan POST landing tetap konsisten

## Guardrail

- default tetap `off`, jadi perilaku produksi tidak berubah sampai flag diaktifkan eksplisit
- akses langsung ke `landing-shadow-proxy.php` diblok oleh rewrite rule
- rollback cukup dengan menghapus flag atau menjalankan `scripts/disable-landing-shadow.sh`

## Status Awal

- comparison route publik sekarang sudah `safe=20`, termasuk landing `/`
- aktivasi landing baru layak dilakukan setelah guardrail ini tervalidasi