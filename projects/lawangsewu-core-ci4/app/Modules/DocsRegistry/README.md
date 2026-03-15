# DocsRegistry Module Staging

Modul ini akan memegang katalog dokumentasi dan walkthrough Lawangsewu.

## Scope

- route `/walkthrough`
- registry dokumen Markdown, PDF, dan HTML
- metadata pencarian dan kategori dokumen
- link cepat ke PDF master dan widget directory

## Source Lama

- `/var/www/html/lawangsewu/Walkthrough-DBPrakom/index.html`
- `/var/www/html/lawangsewu/Walkthrough-DBPrakom/html/index.html`
- `/var/www/html/lawangsewu/Walkthrough-DBPrakom/md/*`
- `/var/www/html/lawangsewu/Walkthrough-DBPrakom/pdf/*`

## Boundary Penting

- walkthrough harus menjadi registry data, bukan HTML statis permanen
- route `/walkthrough` tetap dipertahankan
- file dokumen asli tetap boleh hidup, tetapi indeks utamanya sebaiknya dibangun dari provider

## Target Refactor

1. bekukan daftar dokumen ke provider terstruktur
2. sediakan controller staging untuk route `/walkthrough`
3. siapkan view adapter agar indeks dokumen tidak lagi bergantung pada file HTML lama