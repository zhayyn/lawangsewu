# Lawangsewu Node Workspace (Isolated)

Workspace ini terpisah dari PHP yang sedang berjalan.

## Tujuan
- Menyiapkan pondasi service Node.js tanpa mengganggu endpoint PHP existing.
- Bisa dipakai bertahap untuk webhook receiver, queue worker, atau API tambahan.

## Jalankan lokal

```bash
cd /var/www/html/lawangsewu/gateway-node
cp .env.example .env
npm run start
```

Default listen: `127.0.0.1:8787`.

## Endpoint
- `GET /health`
- `GET /api/v1/pengumuman?source=all|ma|badilag&limit=10`
- `GET /api/v1/pengumuman/ma?limit=10`
- `GET /api/v1/pengumuman/badilag?limit=10`

## Struktur yang dipakai (rekomendasi)
- `src/server.js` → routing endpoint API
- `src/services/pengumumanService.js` → logika web scraping + cache TTL

## Variabel environment scraper
- `SCRAPER_TIMEOUT_MS` (default `12000`)
- `SCRAPER_CACHE_TTL_MS` (default `180000`)
- `SCRAPER_DEFAULT_LIMIT` (default `10`)
- `SCRAPER_MAX_LIMIT` (default `30`)
- `SCRAPER_MA_URL` (default `https://www.mahkamahagung.go.id/id/pengumuman`)
- `SCRAPER_MA_FALLBACK_URL` (default `https://www.mahkamahagung.go.id/id/berita`)
- `SCRAPER_MA_HOME_URL` (default `https://www.mahkamahagung.go.id/id`)
- `SCRAPER_BADILAG_URL` (default `https://badilag.mahkamahagung.go.id/pengumuman-elektronik`)
- `NODE_CORS_ORIGIN` (default `*`)

## Contoh pemakaian dari website PA Semarang

```javascript
fetch('http://127.0.0.1:8787/api/v1/pengumuman?source=all&limit=5')
	.then((r) => r.json())
	.then((data) => {
		// data.bySource.ma.items dan data.bySource.badilag.items
		console.log(data);
	});
```

## Catatan keamanan
- Tidak ada binding ke `0.0.0.0` secara default.
- Tidak ada perubahan Apache/PHP route existing.
