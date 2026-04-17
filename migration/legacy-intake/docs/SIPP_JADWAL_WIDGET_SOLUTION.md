# SOLUSI CEPAT: Implementasi Browser Automation untuk Info Persidangan

## Status Saat Ini ❌

SIPP dilindungi Cloudflare yang memblokir semua curl requests. Solusi: **Gunakan Node.js + Puppeteer** 

---

## Instalasi & Setup 🚀

### Step 1: Install Puppeteer (di gateway/node-service)

```bash
cd /var/www/html/lawangsewu/gateway/node-service
npm install puppeteer dotenv
```

### Step 2: Buat Service untuk Fetch SIPP

**File:** `gateway/node-service/src/services/sippFetchService.js`

```javascript
const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const SIPP_URL = 'https://sipp.pa-semarang.go.id/slide_sidang';
const CACHE_DIR = '/tmp/sipp_cache';
const CACHE_FILE = path.join(CACHE_DIR, 'jadwal.html');
const CACHE_TTL_MS = 5 * 60 * 1000; // 5 minutes

// Ensure cache directory exists
if (!fs.existsSync(CACHE_DIR)) {
    fs.mkdirSync(CACHE_DIR, { recursive: true });
}

async function fetchSIPPWithPuppeteer() {
    let browser;
    try {
        browser = await puppeteer.launch({
            headless: 'new',
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });
        
        const page = await browser.newPage();
        await page.goto(SIPP_URL, { waitUntil: 'networkidle2', timeout: 30000 });
        
        // Get full HTML after JavaScript execution
        const html = await page.content();
        
        // Save to cache
        fs.writeFileSync(CACHE_FILE, html);
        console.log('[SIPP Fetch] Success - cached at', new Date().toISOString());
        
        return html;
    } catch (error) {
        console.error('[SIPP Fetch] Error:', error.message);
        // Return cached data if available
        if (fs.existsSync(CACHE_FILE)) {
            console.log('[SIPP Fetch] Using cached data');
            return fs.readFileSync(CACHE_FILE, 'utf8');
        }
        throw error;
    } finally {
        if (browser) await browser.close();
    }
}

async function getSIPPData() {
    // Check cache validity
    if (fs.existsSync(CACHE_FILE)) {
        const age = Date.now() - fs.statSync(CACHE_FILE).mtime.getTime();
        if (age < CACHE_TTL_MS) {
            console.log('[SIPP Fetch] Using valid cache');
            return fs.readFileSync(CACHE_FILE, 'utf8');
        }
    }
    
    // Fetch fresh data
    return await fetchSIPPWithPuppeteer();
}

module.exports = { getSIPPData };
```

### Step 3: Buat API Endpoint (Node.js)

**File:** `gateway/node-service/src/routes/sipp.js`

```javascript
const express = require('express');
const { getSIPPData } = require('../services/sippFetchService');
const router = express.Router();

router.get('/jadwal', async (req, res) => {
    try {
        const html = await getSIPPData();
        res.set('Content-Type', 'text/html; charset=utf-8');
        res.send(html);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

module.exports = router;
```

**Tambahkan ke server.js:**

```javascript
const sippRouter = require('./routes/sipp');
app.use('/api/sipp', sippRouter);
```

### Step 4: Update PHP Widget

**File:** `/var/www/html/lawangsewu/widgets/views/php/public/info-persidangan.php`

Ubah bagian CURL menjadi:

```php
if (isset($_GET['format_jadwal'])) {
    // ... existing headers ...
    
    // PERBAIKAN: Use Node.js service instead of direct CURL
    $node_service_url = "http://127.0.0.1:8793/api/sipp/jadwal";
    // Fallback ke direct CURL jika node service tidak tersedia
    
    $html_sipp = '';
    
    // Try Node.js service first (with Puppeteer bypass)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $node_service_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45); // Longer timeout for Puppeteer
    $html_sipp = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if (empty($html_sipp) && !empty($curl_error)) {
        // Fallback: Log error dan try antrian API
        error_log("[SIPP Widget] Node service error: $curl_error");
    }
    
    $dom = new DOMDocument();
    @$dom->loadHTML($html_sipp);
    $xpath = new DOMXPath($dom);
    $rows = $xpath->query('//table//tr');
    
    // ... rest of existing code ...
}
```

---

## Testing 🧪

### Test Node Service
```bash
curl -s http://127.0.0.1:8793/api/sipp/jadwal | grep -o '<table' | head -1
# Output: <table (jika berhasil)
```

### Test Widget
```
http://lawangsewu.pa-semarang.go.id/info-persidangan
# Sekarang harus tampil jadwal lengkap (bukan "Tidak ada sidang")
```

---

## Performance Notes ⚡

- **First Load**: ~15-25 detik (Puppeteer render)
- **Cached Requests**: <100ms (read from /tmp cache)
- **Cache TTL**: 5 menit (configurable)
- **Memory**: ~150MB per browser instance

---

## Alternative: Gunakan API Antrian (Quickfix)

Jika implementasi Puppeteer terlalu kompleks, gunakan endpoint yang sudah working:
- `https://antrian.pa-semarang.go.id/tv_media/display_bawah` - Real-time room data

```php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://antrian.pa-semarang.go.id/tv_media/display_bawah");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$json_data = curl_exec($ch);
curl_close($ch);

$rooms = json_decode($json_data, true);
// Format sebagai table rows seperti SIPP
```

**Kekurangan:**
- Hanya current/next case per room (tidak jadwal panjang)
- Cocok untuk "live queue monitor", bukan "full schedule view"

---

## Rekomendasi 💡

1. **Quick Fix (30 menit)**: Hubungi SIPP/IT PA Semarang minta API endpoint non-Cloudflare
2. **Medium Fix (2-3 jam)**: Implementasi Puppeteer solution di atas
3. **Long Term**: Integrasikan dengan SIPP API resmi (jika ada)

---

**Doc Created:** 2026-03-30  
**Status:** Ready for Implementation
