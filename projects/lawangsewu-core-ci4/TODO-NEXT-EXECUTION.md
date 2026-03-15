# Next Execution Todo

Urutan aman berikutnya untuk mulai implementasi teknis:

1. rapikan shell runtime staging agar makin mendekati struktur CI4 asli
2. lanjutkan pemetaan route publik wave berikut ke render lokal shell staging
3. perluas compatibility route dari `config/route-contracts.json` sampai alias lama bisa dipetakan penuh ke shell atau redirect kanonik
4. perluas audit akses dari request-level ke launcher/action-level
5. siapkan widget directory runtime dari registry, bukan HTML statis
6. siapkan walkthrough runtime dari registry, bukan indeks HTML statis
7. mulai evaluasi cutover bertahap per route family

Catatan progres terbaru:

- `ROUTE-COVERAGE-REPORT.php` sudah bisa dipakai untuk mengukur route in-scope yang benar-benar hidup di shell staging
- gunakan report ini sebelum menambah route family baru agar readiness bisa dibandingkan antar iterasi
- `ROUTE-FAMILY-READINESS.php` sekarang bisa dipakai untuk melihat family mana yang sudah siap shadow dan mana yang masih tertahan oleh gap smoke auth
- `ROUTE-FAMILY-CUTOVER-MATRIX.md` sekarang menjadi acuan urutan shadow publik dan hold area portal auth

Jangan lakukan langkah berikut sebelum gelombang 1 stabil:

- memindahkan WA Caraka runtime
- mengubah route publik existing tanpa alias
- mengubah logout/login lintas app tanpa smoke test
- mengganti `/daftar-widget` langsung ke runtime baru tanpa view adapter yang diuji
- mengganti `/walkthrough` langsung ke runtime baru tanpa view adapter yang diuji