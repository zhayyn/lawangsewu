# Panduan Integrasi Pengumuman MA + Badilag ke PA Semarang

Dokumen ini menjelaskan cara pakai feed pengumuman dari Lawangsewu ke website `pa-semarang.go.id` (IIS), termasuk mode aman dengan validasi `origin` dan auto-height iframe.

## 1) Endpoint yang tersedia

### API JSON (proxy publik Lawangsewu)
- `GET /lawangsewu/api/pengumuman-rss?source=all|ma|badilag&limit=1..30`
- `GET /lawangsewu/api/pengumuman-rss/ma?limit=...`
- `GET /lawangsewu/api/pengumuman-rss/badilag?limit=...`

Contoh:
- `http://192.168.88.9/lawangsewu/api/pengumuman-rss?source=all&limit=5`

### Halaman siap embed
- Versi lengkap (ada header + filter + refresh):
  - `http://192.168.88.9/lawangsewu/pa-semarang-pengumuman?source=all&limit=8`
- Versi embed clean (tanpa header):
  - `http://192.168.88.9/lawangsewu/pa-semarang-pengumuman-embed?source=all&limit=5`

## 2) Snippet aman untuk IIS (disarankan)

Tempel kode berikut di halaman IIS `pa-semarang.go.id`:

```html
<iframe
  id="lwPengumumanFrame"
  src="http://192.168.88.9/lawangsewu/pa-semarang-pengumuman-embed?source=all&limit=5"
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

## 3) Cara penggunaan parameter

- `source=all` → gabungan MA + Badilag (default direkomendasikan)
- `source=ma` → hanya Mahkamah Agung
- `source=badilag` → hanya Badilag
- `limit=5` → jumlah item tampil (disarankan 5–8 untuk homepage)

Contoh variasi:
- `.../pa-semarang-pengumuman-embed?source=ma&limit=5`
- `.../pa-semarang-pengumuman-embed?source=badilag&limit=8`

## 4) Checklist implementasi cepat

1. Buka halaman CMS/ASPX/HTML di IIS PA Semarang.
2. Tempel snippet iframe aman (bagian 2).
3. Simpan dan publish.
4. Hard refresh browser (`Ctrl+F5`).
5. Pastikan card pengumuman muncul dan tinggi iframe menyesuaikan otomatis.

## 5) Troubleshooting

### A. Iframe kosong
- Cek URL embed langsung:
  - `http://192.168.88.9/lawangsewu/pa-semarang-pengumuman-embed?source=all&limit=5`
- Jika tidak terbuka, cek jaringan/firewall antara IIS dan server Lawangsewu.

### B. Tinggi iframe tidak menyesuaikan
- Pastikan script `window.addEventListener('message', ...)` ikut ditempel di parent.
- Pastikan `allowedOrigins` memuat origin Lawangsewu yang benar.

### C. Data tidak tampil
- Uji API langsung:
  - `http://192.168.88.9/lawangsewu/api/pengumuman-rss?source=all&limit=3`
- Jika API gagal, cek service Node Lawangsewu di `127.0.0.1:8787`.

## 6) Rekomendasi operasional

- Untuk homepage: gunakan versi embed clean (`limit=5` atau `8`).
- Untuk halaman khusus berita/publikasi: gunakan versi lengkap.
- Saat go-live domain production, ganti `CHILD_ORIGIN` ke domain final Lawangsewu.
