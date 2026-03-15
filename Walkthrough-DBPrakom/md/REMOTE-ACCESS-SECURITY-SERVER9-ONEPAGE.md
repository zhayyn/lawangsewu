# Ringkasan 1 Halaman
## Keamanan Akses Remote Server 9

Tanggal: 2026-03-09  
Status: Aktif (diterapkan di server)

### Tujuan
Menjaga akses admin Server 9 tetap aman, tetap bisa dipakai dari iPad, dan tidak bergantung pada satu akun cloud yang bisa dibajak.

### Masalah Awal
Model awal memakai VS Code Tunnel berbasis akun cloud. Risiko utamanya:

- Jika akun cloud dicuri, ada peluang penyalahgunaan akses remote.
- Sesi/token bisa tertinggal di server bila tidak dicabut dengan benar.
- Ketergantungan tinggi pada identitas eksternal.

### Keputusan Arsitektur
Dipilih arsitektur:

- Jalur utama admin: `WireGuard + SSH`
- Jalur cloud tunnel: `dinonaktifkan total` (masked)

Perumpamaan:

- WireGuard = jalan privat ke area internal.
- SSH = pintu utama dengan kunci admin.
- UFW limit = satpam yang membatasi percobaan masuk berulang.
- Tunnel cloud = pintu tamu online (saat ini ditutup).

### Implementasi yang Sudah Berjalan
1. WireGuard aktif di `wg0`, port `51830/udp`.
2. Peer admin aktif menggunakan subnet VPN `10.19.9.0/24`.
3. SSH tetap aktif, dengan `LIMIT IN 22/tcp` pada UFW.
4. Seluruh service tunnel sudah dikunci:
- `vscode-tunnel-lawangsewu.service` -> `masked`
- `vscode-tunnel-lawangsewu-ms.service` -> `masked`
5. Login user tunnel dicabut (`not logged in`).

### Penilaian Keamanan (Praktis)
- Risiko dari pembajakan akun cloud untuk akses server: **rendah**.
- Risiko brute-force SSH publik: **terkendali** (rate limit aktif).
- Ketersediaan akses admin: **baik** (jalur utama VPN + SSH).

### Dampak Operasional
- Admin tetap bisa bekerja dari iPad melalui WireGuard + SSH.
- Layanan produksi tidak perlu perubahan besar.
- Tidak ada ketergantungan akses server ke akun GitHub tertentu.

### Tentang GitHub Copilot
- Copilot tetap dapat dipakai normal di perangkat pengguna.
- Akun GitHub untuk Copilot bisa diganti kapan saja di sisi client.
- Pergantian akun Copilot tidak otomatis membuka akses ke server.

### SOP Harian Singkat
1. Connect WireGuard di iPad.
2. SSH ke IP VPN server (bukan IP publik).
3. Kerja administrasi.
4. Disconnect VPN saat selesai.

### SOP Insiden Singkat
Jika akun cloud dicurigai dibajak:

1. Revoke session/token akun cloud.
2. Ubah password dan aktifkan 2FA.
3. Verifikasi server tetap pada kondisi:
- kedua tunnel `masked`
- user tunnel `not logged in`
- `wg0` aktif

### Checklist Verifikasi Cepat
```bash
systemctl is-enabled vscode-tunnel-lawangsewu.service
systemctl is-enabled vscode-tunnel-lawangsewu-ms.service
sudo -u lawangsewu-vscode -H /usr/local/bin/code-tunnel-lawangsewu tunnel user show
sudo -u lawangsewu-vscode-ms -H /usr/local/bin/code-tunnel-lawangsewu tunnel user show
systemctl status wg-quick@wg0 --no-pager
wg show
ufw status numbered
```

Ekspektasi:
- kedua service tunnel: `masked`
- kedua user tunnel: `not logged in`
- WireGuard aktif (`wg0 up`)

---
Dokumen ini ditujukan untuk kebutuhan briefing cepat pimpinan dan audit internal.
