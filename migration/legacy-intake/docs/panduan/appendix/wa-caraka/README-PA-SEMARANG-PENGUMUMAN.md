# Panduan Halaman Pengumuman dan Berita Pengadilan

developed by dbprakom

Dokumen ini menjelaskan komponen publik Lawangsewu yang dipakai untuk menampilkan pengumuman dan berita, cara membukanya dari jaringan `192.168.88.9`, perbedaan fungsi tiap halaman, serta cara integrasi ke halaman lain seperti IIS `pa-semarang.go.id`.

## 1) Halaman yang tersedia

### A. Berita Pengadilan
- URL: `http://192.168.88.9/berita-pengadilan`
- Route: `/berita-pengadilan`
- Fungsi: halaman agregasi berita dan publikasi peradilan dalam satu tampilan.

Isi halaman ini:
- Blok `Berita PA Semarang`: mengambil 3 berita terbaru dari feed kategori berita situs PA Semarang.
- Blok `Pengumuman & Artikel PA Semarang`: tab untuk kategori pengumuman dan artikel dari situs PA Semarang.
- Blok `RSS Feed Peradilan`: tab sumber `MA`, `Badilag`, dan `PTA Semarang`.

Karakter halaman ini:
- Lebih kaya konten.
- Cocok untuk halaman portal atau dashboard informasi.
- Menggabungkan sumber internal PA Semarang dan sumber RSS peradilan.
- Tidak ditujukan sebagai embed iframe kecil yang super-ringkas.

### B. Feed Pengumuman Lengkap
- URL final domain: `https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan?source=all&limit=8`
- URL LAN/staging: `http://192.168.88.9/lawangsewu/pa-semarang-pengumuman?source=all&limit=8`
- Route kanonik domain final: `/pengumuman-peradilan`
- Route kompatibilitas existing: `/pa-semarang-pengumuman`
- Fungsi: menampilkan daftar pengumuman resmi dalam format halaman penuh.

Karakter halaman ini:
- Fokus pada pengumuman saja.
- Sumber utama: `Mahkamah Agung` dan `Badilag`.
- Ada tab sumber dan tampilan list yang lebih formal.
- Cocok untuk halaman khusus pengumuman.

### C. Feed Pengumuman Embed
- URL final domain: `https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan-embed?source=all&limit=5`
- URL LAN/staging: `http://192.168.88.9/lawangsewu/pa-semarang-pengumuman-embed?source=all&limit=5`
- Route kanonik domain final: `/pengumuman-peradilan-embed`
- Route kompatibilitas existing: `/pa-semarang-pengumuman-embed`
- Fungsi: versi embed ringan untuk dipasang ke halaman parent dengan iframe.

Karakter halaman ini:
- Fokus pada pengumuman saja.
- Dirancang untuk auto-height iframe.
- Cocok untuk homepage atau panel samping situs lain.

### D. Widget Pengumuman Ringkas
- URL final domain: `https://lawangsewu.pa-semarang.go.id/widget-pengumuman`
- URL LAN/staging: `http://192.168.88.9/lawangsewu/pengumuman-rss-widget`
- Route kanonik domain final: `/widget-pengumuman`
- Route kompatibilitas existing: `/pengumuman-rss-widget`
- Fungsi: widget pengumuman sederhana dengan tombol refresh.

Karakter halaman ini:
- Lebih ringan dari versi penuh.
- Tetap fokus hanya pada item pengumuman.
- Cocok untuk tampilan cepat atau eksperimen internal.

## 2) API JSON yang dipakai

- `GET /api/pengumuman-rss?source=all|ma|badilag|pta&limit=1..30`
- `GET /api/pengumuman-rss/ma?limit=...`
- `GET /api/pengumuman-rss/badilag?limit=...`
- `GET /api/pengumuman-rss/pta?limit=...`

Contoh:
- `http://192.168.88.9/api/pengumuman-rss?source=all&limit=5`
- `http://192.168.88.9/api/pengumuman-rss?source=pta&limit=3`

## 3) Perbedaan Berita Pengadilan vs Feed Pengumuman

### A. Dari sisi tujuan
- `berita-pengadilan` dipakai untuk menampilkan campuran berita, pengumuman, artikel, dan RSS peradilan dalam satu halaman.
- `pengumuman-peradilan` dan `pengumuman-peradilan-embed` dipakai khusus untuk pengumuman resmi.

### B. Dari sisi isi konten
- `berita-pengadilan` berisi beberapa blok konten sekaligus.
- `feed pengumuman` hanya menampilkan daftar item pengumuman.

### C. Dari sisi sumber data
- `berita-pengadilan` mengambil data dari feed situs PA Semarang untuk berita, pengumuman, dan artikel, lalu menggabungkannya dengan RSS MA, Badilag, dan PTA Semarang.
- `feed pengumuman` terutama mengambil data dari API proxy Lawangsewu `/api/pengumuman-rss`, yang difokuskan untuk sumber pengumuman peradilan.

### D. Dari sisi penggunaan
- `berita-pengadilan` cocok jika ingin halaman portal informasi yang lebih hidup dan lebih kaya.
- `feed pengumuman lengkap` cocok jika ingin halaman khusus pengumuman resmi.
- `feed pengumuman embed` cocok jika ingin disisipkan ke website lain via iframe.
- `widget pengumuman ringkas` cocok jika hanya perlu daftar pendek yang cepat tampil.

### E. Dari sisi tampilan
- `berita-pengadilan` tampil seperti portal atau dashboard.
- `feed pengumuman` tampil seperti daftar resmi yang lebih fokus.

## 4) Kapan memakai yang mana

- Gunakan `berita-pengadilan` jika yang dibutuhkan adalah halaman publikasi terpadu.
- Gunakan `pengumuman-peradilan` jika yang dibutuhkan adalah halaman pengumuman formal.
- Gunakan `pengumuman-peradilan-embed` jika konten harus ditempel ke halaman IIS atau CMS lain.
- Gunakan `widget-pengumuman` jika butuh widget ringkas tanpa layout portal penuh.

## 5) Cara membuka dari VS Code

Untuk workspace ini, jangan mengandalkan klik file sumber lalu preview file.

Alasannya:
- Implementasi file ada di `widgets/views/html/public/` dan `widgets/views/php/public/`.
- Akses langsung ke `widgets/views/*` diblokir `403`.
- Halaman publik dibuka lewat route rewrite Apache.

Cara yang benar dari VS Code:

1. Buka `Command Palette`.
2. Jalankan `Simple Browser: Show`.
3. Masukkan URL yang ingin dilihat, misalnya:
  - `https://lawangsewu.pa-semarang.go.id/berita-pengadilan`
  - `https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan`
  - `https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan-embed`
  - `https://lawangsewu.pa-semarang.go.id/widget-pengumuman`

Alternatif lewat terminal VS Code:

```bash
xdg-open https://lawangsewu.pa-semarang.go.id/berita-pengadilan
xdg-open https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan
```

## 6) Snippet aman untuk IIS

Tempel kode berikut di halaman IIS `pa-semarang.go.id` jika ingin menampilkan feed pengumuman versi embed:

```html
<iframe
  id="lwPengumumanFrame"
  src="https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan-embed?source=all&limit=5"
  style="width:100%;height:560px;border:0;overflow:hidden;"
  scrolling="no"
  loading="lazy"
  referrerpolicy="strict-origin-when-cross-origin">
</iframe>

<script>
(function () {
  var iframe = document.getElementById('lwPengumumanFrame');
  var CHILD_ORIGIN = 'http://192.168.88.9';

  window.addEventListener('message', function (event) {
    if (!event || !event.data) return;
    if (event.data.type !== 'LAWANGSEWU_IFRAME_RESIZE') return;
    if (event.origin !== CHILD_ORIGIN) return;

    var nextHeight = parseInt(event.data.height, 10);
    if (!isNaN(nextHeight) && nextHeight > 100 && nextHeight < 4000) {
      iframe.style.height = (nextHeight + 8) + 'px';
    }
  });
})();
</script>
```

## 7) Parameter feed pengumuman

- `source=all` berarti gabungan MA dan Badilag.
- `source=ma` berarti hanya Mahkamah Agung.
- `source=badilag` berarti hanya Badilag.
- `limit=5` berarti jumlah item yang ditampilkan.

Contoh:
- `https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan-embed?source=ma&limit=5`
- `https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan-embed?source=badilag&limit=8`

## 8) Troubleshooting

### A. Halaman tidak tampil
- Uji langsung lewat browser: `http://192.168.88.9/berita-pengadilan`
- Uji langsung feed pengumuman: `https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan`
- Jika gagal, cek Apache dan rewrite route.

### B. Embed kosong
- Cek URL embed langsung: `https://lawangsewu.pa-semarang.go.id/pengumuman-peradilan-embed?source=all&limit=5`
- Cek jaringan antara IIS dan server Lawangsewu.

### C. Data pengumuman tidak tampil
- Uji API langsung: `http://192.168.88.9/api/pengumuman-rss?source=all&limit=3`
- Jika API gagal, cek service Node Lawangsewu di `127.0.0.1:8787`.

### D. Berita pengadilan kosong saat dibuka dari preview file
- Jangan buka file sumber langsung dari editor.
- Buka lewat URL server karena halaman ini mengandalkan route Apache dan fetch ke endpoint server.

## 9) Rekomendasi operasional

- Untuk homepage situs lain: gunakan `pengumuman-peradilan-embed`.
- Untuk halaman informasi campuran: gunakan `berita-pengadilan`.
- Untuk halaman pengumuman resmi yang fokus: gunakan `pengumuman-peradilan`.
- Saat go-live domain production, ganti `192.168.88.9` ke domain final yang berlaku.
