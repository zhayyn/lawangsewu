# Keamanan Akses Remote Server 9 (Versi Sederhana)

Dokumen ini adalah versi ringkas dan mudah dipahami dari kebijakan akses remote Server 9.

## Gambaran Besar

Bayangkan Server 9 itu seperti rumah penting yang berisi dokumen negara.

- Dulu kita sempat pakai "pintu tamu online" (cloud tunnel berbasis akun).
- Sekarang kita pilih "jalan pribadi + kunci sendiri" (WireGuard + SSH).

Tujuannya sederhana: kalau akun cloud seseorang dibajak, orang itu tetap tidak bisa masuk ke server lewat jalur admin utama.

## Perumpamaan Sederhana

- `WireGuard` = jalan tol pribadi ke kompleks perumahan.
- `SSH` = pintu rumah utama yang perlu kunci.
- `UFW limit` = satpam yang membatasi orang coba-coba kunci berkali-kali.
- `VS Code Tunnel` = pintu tamu pintar berbasis akun internet.

Keputusan sekarang:

- Pintu tamu pintar dimatikan.
- Masuk hanya lewat jalan pribadi + pintu utama.

## Kenapa Dipilih Model Ini

1. Lebih tahan jika akun cloud (misal GitHub) dicuri.
2. Kontrol akses lebih jelas karena pakai kunci yang kita pegang sendiri.
3. Cocok untuk operasional dari iPad tanpa membuka pintu publik tambahan.
4. Tidak mengganggu layanan lama di server.

## Tingkat Keamanan (Bahasa Praktis)

- Risiko dari pembajakan akun cloud: **rendah** (untuk akses server), karena tunnel cloud dimatikan.
- Risiko percobaan login acak dari internet: **lebih terkendali**, karena SSH dibatasi rate limit.
- Risiko kehilangan akses admin: **rendah-menengah**, karena ada jalur cadangan yang stabil (WireGuard + SSH).

Catatan: keamanan terbaik tetap butuh disiplin rotasi kunci dan audit log rutin.

## Cara Pakai Harian (iPad)

1. Nyalakan aplikasi WireGuard.
2. Sambungkan profil iPad.
3. SSH ke IP VPN server (bukan IP publik).
4. Selesai kerja, putuskan VPN.

## Kalau Akun GitHub Dicuri, Apa yang Terjadi?

Perumpamaan:

- Dompet digital (akun GitHub) mungkin dicuri,
- Tapi gerbang rumah utama kita tidak pakai dompet itu.

Dampak praktis:

- Pelaku tidak bisa masuk server lewat tunnel cloud, karena tunnel sudah ditutup.
- Akses server tetap aman lewat jalur WireGuard + SSH milik admin.

## Penggunaan GitHub Copilot Tetap Aman?

Ya.

- Copilot dipakai di perangkat kamu (client), bukan membuka pintu server.
- Kamu tetap bisa ganti-ganti akun GitHub untuk Copilot kapan saja.
- Ganti akun Copilot tidak otomatis membuka akses ke Server 9.

## Checklist Singkat (Untuk Tim Non-Teknis)

- [ ] Tunnel cloud dimatikan.
- [ ] Akses admin hanya lewat WireGuard + SSH.
- [ ] SSH rate limit aktif.
- [ ] Kunci akses disimpan aman.
- [ ] Audit login dilakukan berkala.

---

Intinya:

Kita memilih jalur akses yang lebih "punya kita sendiri" dan lebih tahan terhadap risiko akun cloud yang dibajak.
