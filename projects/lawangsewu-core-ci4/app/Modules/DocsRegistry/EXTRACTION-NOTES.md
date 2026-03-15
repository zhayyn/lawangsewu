# DocsRegistry Extraction Notes

Source utama walkthrough saat ini:

- `/var/www/html/lawangsewu/Walkthrough-DBPrakom/index.html`
- `/var/www/html/lawangsewu/Walkthrough-DBPrakom/html/index.html`
- `/var/www/html/lawangsewu/Walkthrough-DBPrakom/md/*`
- `/var/www/html/lawangsewu/Walkthrough-DBPrakom/pdf/*`

## Target Ekstraksi

### Pisahkan indeks dari HTML statis

- daftar dokumen utama
- kategori keamanan, migrasi, operasi, dan bundle
- link MD/PDF master
- shortcut ke `/daftar-widget`

### Pertahankan perilaku yang sudah disukai

- route `/walkthrough`
- link `/walkthrough/master-pdf`
- akses file turunan `/walkthrough/md/*`, `/walkthrough/pdf/*`, `/walkthrough/html/*`

### Jangan campur ke modul lain

- daftar dokumen jangan disimpan permanen di portal
- widget registry tetap modul terpisah
- file asli tetap hidup sebagai artefak sumber sampai adapter runtime selesai

## Rule Final

DocsRegistry menangani indeks dokumentasi dan metadata walkthrough, bukan auth portal atau katalog widget publik.