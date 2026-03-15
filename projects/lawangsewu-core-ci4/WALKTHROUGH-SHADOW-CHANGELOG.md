# Walkthrough Shadow Change

Perubahan ini mengaktifkan shadow terbatas untuk route `/walkthrough`.

Implementasi:

- `.htaccess` sekarang memeriksa flag `runtime-flags/walkthrough-shadow-on.flag`
- jika flag ada, `/walkthrough` diarahkan ke `walkthrough-shadow-proxy.php`
- proxy memanggil shell staging `projects/lawangsewu-core-ci4/public/index.php`

Guardrail:

- hanya route `/walkthrough` yang dipindahkan
- file proxy tidak boleh diakses langsung dari browser
- rollback cukup dengan menghapus flag atau menjalankan `scripts/disable-walkthrough-shadow.sh`

Status awal:

- flag sudah dibuat aktif sebagai shadow terbatas pertama