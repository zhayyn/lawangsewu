# Checklist Migrasi Lawangsewu ke Laptop Baru (Linux)

Dokumen ini untuk pindah environment Lawangsewu ke laptop baru.

## Jawaban singkat: cukup login GitHub di VS Code?
Tidak cukup.

Login GitHub di VS Code hanya membantu autentikasi (`clone`, `pull`, `push`).
Aplikasi tetap butuh runtime + konfigurasi server + environment variable + service background.

## 1) Prasyarat di laptop baru
- OS Linux (Ubuntu/Debian)
- Akses jaringan ke host terkait (DB/SIPP/internet)
- Hak sudo/root
- GitHub access ke repository

Install paket dasar:

```bash
sudo apt update
sudo apt install -y git curl unzip apache2 php php-cli php-curl php-xml php-mbstring libapache2-mod-php
```

Install Node.js 20+:

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

## 2) Ambil source code
Contoh target folder:

```bash
sudo mkdir -p /var/www/html
cd /var/www/html
sudo git clone <URL-REPO-ANDA> lawangsewu
sudo chown -R $USER:$USER /var/www/html/lawangsewu
```

## 3) Konfigurasi Apache
Aktifkan module yang dibutuhkan:

```bash
sudo a2enmod rewrite headers
sudo systemctl restart apache2
```

Pastikan project ada di document root Apache dan file `.htaccess` di root project ikut terbaca.

Jika perlu, pastikan virtual host mengizinkan override:

```apache
<Directory /var/www/html/lawangsewu>
    AllowOverride All
    Require all granted
</Directory>
```

## 4) Konfigurasi environment PHP
Buat file `.env` dari template:

```bash
cd /var/www/html/lawangsewu
cp .env.example .env
```

Isi minimal sesuai kebutuhan server baru, terutama endpoint SIPP/DB (jika dipakai modul statistik):
- `LW_SIPP_BASE_URL`
- `LW_SIPP_SLIDE_URL` (opsional)
- `LW_STAT_DB_HOST`, `LW_STAT_DB_USER`, `LW_STAT_DB_PASS`, `LW_STAT_DB_NAME` (jika statistik pakai DB)

## 5) Setup Node gateway (pengumuman MA/Badilag)

```bash
cd /var/www/html/lawangsewu/gateway-node
cp .env.example .env
npm install
npm run start
```

Tes endpoint lokal Node:

```bash
curl -s "http://127.0.0.1:8787/health"
curl -s "http://127.0.0.1:8787/api/v1/pengumuman?source=all&limit=3"
```

## 6) Jalankan Node gateway sebagai service (disarankan)
Buat service systemd:

```bash
sudo tee /etc/systemd/system/lawangsewu-gateway.service >/dev/null <<'EOF'
[Unit]
Description=Lawangsewu Node Gateway
After=network.target

[Service]
Type=simple
WorkingDirectory=/var/www/html/lawangsewu/gateway-node
ExecStart=/usr/bin/npm run start
Restart=always
RestartSec=3
User=www-data
Environment=NODE_ENV=production

[Install]
WantedBy=multi-user.target
EOF
```

Aktifkan service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now lawangsewu-gateway
sudo systemctl status lawangsewu-gateway --no-pager
```

### (Opsional, direkomendasikan) Service fallback port 8788
Tambahkan instance kedua agar proxy tetap hidup saat gateway utama bermasalah:

```bash
sudo tee /etc/systemd/system/lawangsewu-gateway-fallback.service >/dev/null <<'EOF'
[Unit]
Description=Lawangsewu Node Gateway Fallback
After=network.target

[Service]
Type=simple
WorkingDirectory=/var/www/html/lawangsewu/gateway-node
ExecStart=/usr/bin/node /var/www/html/lawangsewu/gateway-node/src/server.js
Restart=always
RestartSec=3
User=root
Group=root
Environment=NODE_ENV=production
Environment=NODE_HOST=127.0.0.1
Environment=NODE_PORT=8788
Environment=NODE_APP_NAME=Lawangsewu Node Gateway Fallback

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable --now lawangsewu-gateway-fallback
sudo systemctl status lawangsewu-gateway-fallback --no-pager
```

Set environment proxy (jika belum):
- `LW_NODE_GATEWAY_BASE=http://127.0.0.1:8787`
- `LW_NODE_GATEWAY_FALLBACK_BASE=http://127.0.0.1:8788`

### Auto-healing cron (disarankan)
Tambahkan health-check tiap menit agar service gateway otomatis restart jika down:

```bash
chmod +x /var/www/html/lawangsewu/scripts/healthcheck-gateway.sh
(crontab -l 2>/dev/null | grep -v 'lawangsewu-gateway-health' ; echo '*/1 * * * * /var/www/html/lawangsewu/scripts/healthcheck-gateway.sh >/dev/null 2>&1 # lawangsewu-gateway-health') | crontab -
```

Log restart otomatis tersimpan di:
- `/var/www/html/lawangsewu/logs/gateway-health.log`

## 7) Verifikasi endpoint Lawangsewu (PHP proxy)

```bash
curl -s "http://127.0.0.1/lawangsewu/api/pengumuman-rss?source=all&limit=3"
curl -s "http://127.0.0.1/lawangsewu/pa-semarang-pengumuman-embed?source=all&limit=5" | head
```

Jika JSON tidak keluar dari endpoint proxy, cek:
- status service `lawangsewu-gateway`
- aturan rewrite di `.htaccess`
- permission file/folder

## 8) Opsional: cron update slide sidang
Jika butuh refresh otomatis file slide:

```bash
crontab -e
```

Contoh jadwal tiap 5 menit:

```cron
*/5 * * * * /usr/bin/php /var/www/html/lawangsewu/widgets/views/generate_slide.php >> /var/www/html/lawangsewu/logs/generate_slide.log 2>&1
```

## 9) Checklist go-live cepat
- [ ] Apache aktif (`systemctl status apache2`)
- [ ] Node gateway aktif (`systemctl status lawangsewu-gateway`)
- [ ] `/lawangsewu/api/pengumuman-rss` mengembalikan JSON `ok: true`
- [ ] Halaman embed tampil dan item feed muncul
- [ ] Link kategori PA (berita/pengumuman/artikel) menuju domain/host yang benar

## 10) Saat pindah staging -> production
Untuk komponen berita pengadilan, cukup ubah 1 baris di:
- `widgets/views/berita-pengadilan.html`

Nilai variabel:
- `PA_BASE_URL = 'http://192.168.88.9/pasemarang'` (staging)
- `PA_BASE_URL = 'https://pa-semarang.go.id'` (production)
