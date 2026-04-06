# Audit Konsolidasi Folder Lawangsewu

Tanggal: 20 Maret 2026

## Ringkasan Eksekusi

1. `pasarjohar/` dipindahkan ke `archive/pasarjohar/`.
2. `gateway-node/` dikonsolidasikan ke `gateway/node-service/`.
3. Dokumen root non-runtime dipindahkan ke `docs/root/`.
4. Handbook resmi dijadikan satu file PDF: `Walkthrough-DBPrakom/LAWANGSEWU-KOMPENDIUM-LENGKAP.pdf`.

## Audit WA Caraka dan WA Caraka Admin

### Temuan

1. Banyak kontrak route, dokumentasi keamanan, smoke script, dan config CI4 yang mengacu pada jalur `wa-caraka-admin/*`.
2. `wa-caraka-admin/` berfungsi sebagai wrapper webroot tipis, sedangkan engine utama tetap di `wa-caraka/`.
3. Penggabungan fisik penuh berisiko memutus kontrak integrasi publik, SSO, dan jalur operasional.

### Rekomendasi Terbaik

1. Tidak digabung total secara fisik.
2. Tetap dipertahankan sebagai dua folder dengan satu domain operasional:
   - engine/runtime: `wa-caraka/`
   - wrapper publik admin: `wa-caraka-admin/`
3. Konsolidasi dilakukan pada level tata kelola dan dokumentasi, bukan memaksa merger struktur path publik.

## Analogi Keputusan

Menggabungkan `wa-caraka` dan `wa-caraka-admin` seperti memindahkan ruang kontrol bandara ke pintu kedatangan saat pesawat sedang aktif beroperasi.

- Secara teori terlihat lebih ringkas.
- Secara operasional meningkatkan risiko gangguan layanan.

Karena itu dipilih pendekatan aman: tata letak tetap terpisah, alur kerjanya dipersatukan.
