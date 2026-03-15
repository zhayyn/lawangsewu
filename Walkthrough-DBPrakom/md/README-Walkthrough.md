# Walkthrough-DBPrakom

developed by dbprakom

Folder ini menjadi pusat dokumentasi operasional Lawangsewu yang sudah dirapikan dalam satu tempat.

## Struktur folder

- `md/` berisi salinan dokumen top-level dalam format Markdown.
- `pdf/` berisi PDF existing dan hasil export walkthrough utama.
- `html/` berisi halaman indeks HTML untuk dibuka langsung dari browser.

## Widget publik utama

- `https://lawangsewu.pa-semarang.go.id/berita-pengadilan`
- `https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan`
- `https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan-embed`
- `https://lawangsewu.pa-semarang.go.id/widget-pengumuman`
- `https://lawangsewu.pa-semarang.go.id/info-persidangan`
- `https://lawangsewu.pa-semarang.go.id/monitor-persidangan`
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

- Indeks HTML: `html/index.html`
- Route publik: `/walkthrough`
- PDF walkthrough utama: `pdf/Walkthrough-DBPrakom-Master.pdf`
- Dokumen panduan publikasi: `md/README-PA-SEMARANG-PENGUMUMAN.md`

## Catatan URL final

URL final widget diseragamkan ke root domain.

Contoh benar:
- `https://lawangsewu.pa-semarang.go.id/berita-pengadilan`
- `https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan`

Contoh legacy yang sekarang akan diarahkan ke slug kanonik pada domain final:
- `https://lawangsewu.pa-semarang.go.id/lawangsewu/berita-pengadilan`
- `https://lawangsewu.pa-semarang.go.id/lawangsewu/pa-semarang-pengumuman`
- `https://lawangsewu.pa-semarang.go.id/pengumuman-rss-widget`