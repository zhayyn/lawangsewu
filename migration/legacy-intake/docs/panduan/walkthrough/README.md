# Walkthrough-DBPrakom

developed by dbprakom

Folder ini menjadi pusat dokumentasi operasional Lawangsewu yang sudah dirapikan dalam satu tempat.

## Struktur folder

- `index.html` sebagai landing/katalog publik dokumentasi dan widget.
- `LAWANGSEWU-KOMPENDIUM-LENGKAP.pdf` sebagai handbook resmi tunggal.
- `md/` dipertahankan sementara sebagai mirror legacy untuk kompatibilitas lama, bukan sumber utama.
- sumber dokumen kanonik (MD/PDF) berada di `docs/appendix/` sesuai kategorinya.

## Widget publik utama

- `https://lawangsewu.pa-semarang.go.id/daftar-widget`
- `https://lawangsewu.pa-semarang.go.id/berita-pengadilan`
- `https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan`
- `https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan-embed`
- `https://lawangsewu.pa-semarang.go.id/widget-pengumuman`
- `https://lawangsewu.pa-semarang.go.id/panduan-embed-pengumuman`
- `https://lawangsewu.pa-semarang.go.id/info-persidangan`
- `https://lawangsewu.pa-semarang.go.id/info-persidangan-hijautua`
- `https://lawangsewu.pa-semarang.go.id/info-persidangan-stabilo`
- `https://lawangsewu.pa-semarang.go.id/monitor-persidangan`
- `https://lawangsewu.pa-semarang.go.id/antrian-persidangan`
- `https://lawangsewu.pa-semarang.go.id/slide_sidang.html`
- `https://lawangsewu.pa-semarang.go.id/dashboard-perkara`
- `https://lawangsewu.pa-semarang.go.id/dashboard-ecourt`
- `https://lawangsewu.pa-semarang.go.id/dashboard-hakim`
- `https://lawangsewu.pa-semarang.go.id/biaya-perkara`
- `https://lawangsewu.pa-semarang.go.id/radius-ghaib`
- `https://lawangsewu.pa-semarang.go.id/radius-kecamatan`
- `https://lawangsewu.pa-semarang.go.id/bridge-server10`
- `https://lawangsewu.pa-semarang.go.id/monitor-wa`

## Perbedaan halaman publikasi

- `berita-pengadilan` adalah halaman agregasi berita, pengumuman, artikel, dan RSS peradilan.
- `pengumuman-peradilan` adalah halaman penuh yang fokus pada daftar pengumuman resmi.
- `pengumuman-peradilan-embed` adalah versi ringan untuk iframe.
- `widget-pengumuman` adalah widget singkat untuk daftar pengumuman pendek.

## Akses cepat

- Indeks HTML: `index.html`
- Route publik: `/walkthrough`
- Daftar widget final: `/daftar-widget`
- Handbook utama PDF: `/docs/laporan/final/LAPORAN-FINAL-LAWANGSEWU-PKAPP-M-AGUS-HAYYUDIN.pdf`
- Dokumen panduan publikasi (kanonik): `/docs/appendix/wa-caraka/README-PA-SEMARANG-PENGUMUMAN.md`

## Status bundle legacy

- render HTML per dokumen dan duplikat `html/index.html` sudah dipensiunkan
- seluruh link PDF aktif sekarang diarahkan ke `docs/appendix/`
- seluruh link MD aktif diarahkan ke `docs/appendix/`
- folder `pdf/` walkthrough dapat dipensiunkan setelah migrasi file fisik selesai

## Catatan URL final

URL final widget diseragamkan ke root domain.

Contoh benar:
- `https://lawangsewu.pa-semarang.go.id/berita-pengadilan`
- `https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan`

Contoh legacy yang sekarang akan diarahkan ke slug kanonik pada domain final:
- `https://lawangsewu.pa-semarang.go.id/lawangsewu/berita-pengadilan`
- `https://lawangsewu.pa-semarang.go.id/lawangsewu/pa-semarang-pengumuman`
- `https://lawangsewu.pa-semarang.go.id/pengumuman-rss-widget`