# WA Caraka Personal Runtime

Runtime WhatsApp personal berbasis **Baileys** untuk dijalankan di WSL (Windows Subsystem for Linux).
Terhubung ke server Lawangsewu via **Tailscale**.

---

## Prasyarat

- Windows dengan WSL2 (Ubuntu 22.04+)
- Node.js 18+ terinstall di WSL
- Tailscale aktif di WSL dan server terhubung

---

## Setup di WSL

### 1. Copy folder ini ke WSL

```bash
# Copy seluruh folder wa-runtime-personal ke WSL (misalnya di home directory)
cp -r /mnt/c/path/ke/wa-runtime-personal ~/wa-runtime-personal
# ATAU jika akses langsung dari path server via Tailscale:
scp -r user@<tailscale-ip-server>:/var/www/lawangsewu/wa-runtime-personal ~/wa-runtime-personal
```

### 2. Buat file `.env`

```bash
cd ~/wa-runtime-personal
cp .env.example .env
nano .env
```

Isi konfigurasi:
```env
PORT=8791
API_TOKEN=token_rahasia_kamu         # Bebas, catat untuk dipakai di Laravel
WEBHOOK_URL=https://lawangsewu.pa-semarang.go.id/api/wa-caraka/webhook/inbound/personal
WEBHOOK_TOKEN=token_rahasia_kamu     # Sama dengan API_TOKEN
```

### 3. Install dependencies

```bash
npm install
```

### 4. Jalankan

```bash
chmod +x start.sh
./start.sh
```

### 5. Cek log

```bash
tail -f server.log
```

---

## Konfigurasi Laravel

Tambahkan ke `.env` server Lawangsewu:

```env
# IP Tailscale WSL kamu (jalankan: tailscale ip di WSL)
LW_WA_V2_BASE_PERSONAL=http://<tailscale-ip-wsl>:8791
LW_WA_V2_TOKEN_PERSONAL=token_rahasia_kamu
WA_CARAKA_PERSONAL_ENABLED=true
```

---

## Scan QR

1. Buka WaCaraka di browser (login sebagai superadmin)
2. Klik ikon **"+ Nomor Personal"** di header
3. Scan QR dengan HP kamu

---

## Troubleshooting

| Masalah | Solusi |
|---|---|
| `Cannot connect to WA runtime` | Pastikan WSL aktif dan server.mjs berjalan |
| QR tidak muncul | Cek `tail -f server.log` di WSL |
| Tailscale tidak konek | Jalankan `tailscale up` di WSL |
| Port 8791 blocked | Pastikan firewall WSL tidak memblokir port tersebut |

---

<!-- developed by dbprakom™ -->
