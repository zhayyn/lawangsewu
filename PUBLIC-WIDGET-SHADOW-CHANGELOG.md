# Public Widget Shadow Change

Perubahan ini menyiapkan shadow terbatas untuk family `public-widget` yang verdict comparison-nya sudah aman di route kanonik.

## Implementasi

- `.htaccess` sekarang mengenali flag `runtime-flags/public-widget-shadow-on.flag`
- jika flag aktif, route publik kanonik yang sudah `safe` diarahkan ke `public-widget-shadow-proxy.php`
- proxy memanggil shell staging `projects/lawangsewu-core-ci4/public/index.php`

## Scope Route

- `info-persidangan`
- `info-persidangan-hijautua`
- `info-persidangan-stabilo`
- `monitor-persidangan`
- `antrian-persidangan`
- `dashboard-perkara`
- `dashboard-ecourt`
- `dashboard-hakim`
- `widget-pengumuman`
- `berita-pengadilan`
- `pengumuman-peradilan`
- `pengumuman-peradilan-embed`
- `panduan-embed-pengumuman`
- `bridge-server10`
- `radius-ghaib`
- `radius-kecamatan`
- `monitor-wa`

## Guardrail

- default tetap `off`, jadi perilaku produksi tidak berubah sampai flag diaktifkan eksplisit
- akses langsung ke `public-widget-shadow-proxy.php` diblok oleh rewrite rule
- alias lama tetap dibiarkan sebagai compatibility layer, bukan langsung di-shadow
- rollback cukup dengan menghapus flag atau menjalankan `scripts/disable-public-widget-shadow.sh`

## Status Awal

- infrastruktur shadow sudah siap
- flag belum diaktifkan
- family `public-widget` sekarang sudah punya bukti comparison `safe` untuk route kanoniknya