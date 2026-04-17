# Troubleshooting Info Persidangan Widget

## Status: "Tidak Ada Sidang Hari Ini"

### Root Cause 🔍

SIPP sekarang dilindungi oleh **Cloudflare Shield** yang memerlukan JavaScript untuk bypass. PHP curl biasa tidak bisa melewati Cloudflare challenge, sehingga tidak ada data jadwal yang tertampil.

**Teknical Details:**
- SIPP URL: `https://sipp.pa-semarang.go.id/slide_sidang`
- Protection: Cloudflare HTTP 403 + JS Challenge
- Result: Empty HTML → No data parsed → "Tidak ada sidang hari ini"

---

## Solusi (Solutions) 🔧

### Option 1: Enable Debug Mode (Untuk Verifikasi)
```url
http://lawangsewu.pa-semarang.go.id/info-persidangan?debug=1
```

Ini akan menampilkan pesan debug dengan:
- CURL error messages
- HTTP response code
- HTML content length
- Cloudflare detection status

---

### Option 2: Use Browser Automation (RECOMMENDED)
Implementasi Puppeteer/Selenium untuk bypass Cloudflare:

```php
// Opsi: Gunakan Puppeteer (Node.js)
// File: gateway/node-service/ atau standalone service
exec('npx puppeteer fetch https://sipp.pa-semarang.go.id/slide_sidang');
```

**Keuntungan:**
- ✅ Bisa bypass Cloudflare
- ✅ Render JavaScript
- ✅ Akurat & real-time

**Kerugian:**
- ⚠️ Butuh Node.js + Puppeteer package
- ⚠️ Resource intensive
- ⚠️ Slower (3-5 detik per request)

---

### Option 3: Alternative Data Source
Jika SIPP API tersedia (non-Cloudflare):
```php
// Kemungkinan endpoint lain:
// - https://sipp.pa-semarang.go.id/api/jadwal
// - https://antrian.pa-semarang.go.id/api/current_cases
// - Check SIPP documentation
```

**Untuk implementasi:**
1. Hubungi tim SIPP/IT PA Semarang
2. Minta API endpoint yang tidak behind Cloudflare
3. Update URL di file: `line 57`

---

### Option 4: Scheduled Cache + Fallback Data
```php
// Implementasi cache dengan TTL 5 menit:
$cache_file = '/tmp/jadwal_sidang_cache.json';
$cache_ttl = 300; // 5 menit

if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_ttl)) {
    // Use cached data
    $html_sipp = file_get_contents($cache_file);
} else {
    // Fetch fresh data
    $html_sipp = curl_exec($ch);
    file_put_contents($cache_file, $html_sipp);
}
```

**Keuntungan:**
- ✅ Reduce SIPP load
- ✅ Faster response
- ✅ Graceful degradation

---

## Current Implementation ✅

**File:** `/var/www/html/lawangsewu/widgets/views/php/public/info-persidangan.php`

**Recent Changes (2026-03-30):**
1. Added comprehensive CURL headers untuk better Cloudflare detection
2. Improved error logging & debug output
3. Added debug mode untuk diagnostics (`?debug=1`)
4. Better timeout handling (increased to 15s)

**Perbaikan yang sudah diterapkan:**
```php
// ✅ User-Agent header (Chrome-like)
// ✅ SSL verification bypass
// ✅ HTTP headers untuk bypass CORS
// ✅ Follow location redirects
// ✅ Error logging untuk debugging
```

---

## Testing & Verification 🧪

### Test 1: Check if SIPP is accessible
```bash
curl -s -I https://sipp.pa-semarang.go.id/slide_sidang | head -3
# Expected: HTTP/2 403 (Cloudflare challenge)
```

### Test 2: Enable debug mode
```
Open: http://lawangsewu.pa-semarang.go.id/info-persidangan?debug=1
Look at: Browser F12 → Elements → Find "Debug:" text
```

### Test 3: Check iframe source
```html
<!-- Widget HTML -->
<iframe src="?format_jadwal=1&t=0" ... ></iframe>

<!-- Direct test -->
http://lawangsewu.pa-semarang.go.id/widgets/views/php/public/info-persidangan.php?format_jadwal=1&debug=1
```

---

## Recommended Action 💡

**Priority 1 (Quick Fix):**
- Contact tim SIPP/IT PA Semarang
- Ask for API endpoint without Cloudflare protection
- Provide new endpoint

**Priority 2 (Medium):**
- Implement Puppeteer-based solution jika API tidak tersedia
- Setup scheduled caching untuk reduce load

**Priority 3 (Long-term):**
- Monitor SIPP for structural changes
- Implement automated testing untuk XPath updates

---

## Contact & Support 📞

Jika masalah persisten:
1. Check browser console: F12 → Console → Check iframe errors
2. View debug info: `?debug=1` parameter
3. Check gateway logs: `/var/www/html/lawangsewu/logs/`
4. Contact: Tim IT PA Semarang / Dev Team

---

**Last Updated:** 2026-03-30 16:15 WIB
**Status:** ⚠️ Requires SIPP API Update
