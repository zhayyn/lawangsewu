# Remote Access Security Server 9

Dokumen ini menjelaskan keputusan arsitektur akses jarak jauh Server 9 setelah evaluasi risiko akun cloud (GitHub/Microsoft), kebutuhan operasional dari iPad, dan target keamanan lingkungan produksi yang tidak boleh mengganggu ekosistem existing.

## 1) Latar Belakang

Sebelumnya, akses remote editor menggunakan VS Code Tunnel berbasis akun cloud. Model ini nyaman, tetapi ada risiko berikut:

- Akun cloud dicuri (account takeover) -> potensi penyalahgunaan tunnel.
- Session/token lama tertinggal di server -> akses tetap hidup walau user lupa mencabut.
- Ketergantungan pada satu identitas eksternal untuk masuk ke server.

Kebutuhan operasional:

- Tetap bisa mengelola server dari iPad.
- Tidak mengganggu layanan produksi yang sudah berjalan di Server 9.
- Memiliki jalur akses cadangan yang tidak tergantung akun GitHub tertentu.

## 2) Keputusan Arsitektur Akhir

Dipilih model:

- Jalur utama: `WireGuard + SSH`.
- Jalur cloud tunnel: dinonaktifkan total (untuk mode hardening saat ini).

Alasan:

- WireGuard memberikan private network terkontrol (IP VPN khusus).
- SSH dapat diperketat dengan kebijakan host/firewall dan audit log standar Linux.
- Tidak ada akses server langsung yang bergantung akun cloud yang bisa dibajak.

## 3) Implementasi yang Sudah Diterapkan

### 3.1 WireGuard

- Interface aktif: `wg0`.
- Port: `51830/udp`.
- Subnet VPN: `10.19.9.0/24`.
- Peer admin aktif saat ini menggunakan alokasi subnet VPN `10.19.9.0/24`.
- Profil client disimpan di luar repo pada server: `/root/secure/wireguard-server9/`.
- Endpoint client WireGuard harus mengarah ke IP publik server atau hostname DNS-only, bukan hostname yang diproxy Cloudflare.

### 3.2 SSH + Firewall

- SSH service aktif.
- UFW rule untuk SSH diubah menjadi rate-limited:
  - `LIMIT IN 22/tcp` (IPv4 + IPv6).
- Rule WireGuard aktif:
  - `ALLOW IN 51830/udp`.

### 3.3 VS Code Tunnel (Dinonaktifkan)

- Service lama (`vscode-tunnel-lawangsewu.service`) sudah:
  - `masked` (symlink ke `/dev/null`).
  - login tunnel user lama dicabut.
- Service ms (`vscode-tunnel-lawangsewu-ms.service`) sudah:
  - `masked` (symlink ke `/dev/null`).
  - login tunnel user ms dicabut.
- Cache tunnel lama dibersihkan pada user lama dan user ms.

Implikasi: tidak ada tunnel cloud aktif yang dapat dipakai untuk akses server.

## 4) Tingkat Keamanan Saat Ini

Penilaian kualitatif untuk remote access:

- Kerahasiaan: **Tinggi**
  - Akses melalui kanal terenkripsi WireGuard + SSH.
  - Tidak ada endpoint tunnel cloud aktif.
- Integritas: **Tinggi**
  - Akses hanya lewat identitas VPN/SSH yang dikontrol admin.
- Ketahanan terhadap kompromi akun GitHub: **Tinggi**
  - Karena jalur tunnel berbasis akun cloud sudah diputus.
- Ketahanan terhadap brute-force publik: **Menengah-Tinggi**
  - SSH rate limit aktif.
  - Rekomendasi tambahan: batasi SSH hanya dari subnet VPN.

Catatan penting:

- Sistem tidak pernah 100% bebas risiko.
- Keamanan akhir tetap bergantung pada:
  - keamanan private key WireGuard/SSH,
  - disiplin rotasi kredensial,
  - monitoring log dan patch rutin.

## 5) Kenapa Akhirnya Menggunakan Model Ini

Model `WireGuard + SSH` dipilih karena:

- Paling konsisten dengan prinsip least dependency (minim ketergantungan pihak ketiga untuk jalur admin).
- Memisahkan akses operasional server dari risiko takeover akun cloud.
- Tetap nyaman untuk iPad (bisa lewat WireGuard app + SSH client).
- Stabil untuk server produksi, tidak perlu membuka service baru ke internet selain port VPN yang sudah terkontrol.

## 6) Dampak ke Penggunaan GitHub Copilot

- GitHub Copilot tetap bisa dipakai normal di device user (VS Code lokal/remote client).
- User bisa ganti-ganti akun GitHub Copilot kapan saja di sisi client.
- Pergantian akun Copilot tidak membuka akses baru ke Server 9 selama tunnel cloud tetap dimatikan.

## 7) SOP Operasional Harian

1. Aktifkan WireGuard di iPad.
2. Pastikan mendapat IP VPN admin yang aktif.
3. SSH ke IP VPN server (contoh `10.19.9.1`), bukan IP publik.
4. Lakukan administrasi dari sesi SSH.
5. Putuskan VPN saat selesai.

## 8) SOP Insiden (Jika Akun Cloud Dicuri)

Walau saat ini tunnel cloud sudah nonaktif, prosedur minimum tetap:

1. Revoke semua session akun cloud dari device yang tidak dikenal.
2. Ganti password dan aktifkan 2FA kuat.
3. Revoke OAuth/PAT mencurigakan.
4. Verifikasi di server:
   - kedua service tunnel harus `masked`.
   - user tunnel harus `not logged in`.
5. Audit log SSH dan firewall untuk anomali.

## 9) Checklist Verifikasi Cepat

Gunakan perintah berikut:

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

- kedua service tunnel: `masked`.
- kedua user tunnel: `not logged in`.
- `wg0` aktif.
- rule `LIMIT 22/tcp` dan `ALLOW 51830/udp` tersedia.

## 10) Opsi Masa Depan (Jika Tunnel Diperlukan Lagi)

Jika suatu saat butuh tunnel lagi untuk kasus khusus:

- Gunakan akun khusus organisasi, bukan akun personal utama.
- Aktifkan tunnel hanya sementara (time-boxed), lalu matikan kembali.
- Audit log setelah sesi selesai.
- Jangan hapus jalur WireGuard+SSH sebagai fallback utama.

---

Dokumen ini merefleksikan posture keamanan remote access Server 9 per 2026-03-09.
