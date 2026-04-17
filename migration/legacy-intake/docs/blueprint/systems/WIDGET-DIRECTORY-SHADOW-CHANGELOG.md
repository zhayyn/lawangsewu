# Widget Directory Shadow Change

Perubahan ini menyiapkan shadow terbatas untuk route `daftar-widget` dan alias `widget-links`.

## Implementasi

- `.htaccess` sekarang mengenali flag `runtime-flags/widget-directory-shadow-on.flag`
- jika flag aktif, `daftar-widget` dan `widget-links` diarahkan ke `widget-directory-shadow-proxy.php`
- proxy memanggil shell staging `projects/lawangsewu-core-ci4/public/index.php`

## Guardrail

- default tetap `off`, jadi perilaku produksi tidak berubah sampai flag diaktifkan eksplisit
- akses langsung ke `widget-directory-shadow-proxy.php` diblok oleh rewrite rule
- rollback cukup dengan menghapus flag atau menjalankan `scripts/disable-widget-directory-shadow.sh`

## Status Awal

- infrastruktur shadow sudah siap
- flag belum diaktifkan
- activation sengaja ditahan sampai verdict `daftar-widget` naik dari `review` ke level yang cukup aman untuk shadow publik