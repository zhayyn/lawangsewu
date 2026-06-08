# Insiden Login Google di Mode Incognito

**Tanggal:** 3 Mei 2026  
**Sistem:** Lawangsewu  
**Area:** Halaman Login / Integrasi Google Sign-In

---

## 1. Ringkasan Masalah
Login menggunakan Google pada mode incognito sempat gagal dan mengarah ke halaman:

`/auth/google/callback?...`

dengan hasil `403 Forbidden`.

Di saat yang sama, pengguna menyampaikan bahwa pada hari sebelumnya login Google sempat berhasil dengan metode selain redirect OAuth biasa. Setelah penelusuran, masalah ternyata bukan pada akun Google pengguna, melainkan pada alur frontend dan bundle browser yang masih memuat jalur login lama.

## 2. Gejala yang Terlihat
- Klik tombol `Lanjutkan dengan Google` kadang memicu alur callback OAuth lama.
- Browser berpindah ke `/auth/google/callback`.
- Halaman menampilkan `403 Forbidden`.
- Pada percobaan awal sempat muncul error `FedCM get() rejects` atau kegagalan pengambilan token Google.

## 3. Akar Masalah
Ada dua sumber masalah yang saling bertumpuk:

1. **Alur login Google di frontend sempat bercampur antara GIS credential flow dan OAuth redirect flow.**  
   Pengguna sebenarnya lebih cocok dengan metode `Google Identity Services` berbasis credential token, bukan redirect callback OAuth.

2. **Browser masih memegang bundle JavaScript lama.**  
   Meskipun source code login sudah diperbaiki, tab browser/incognito yang lama masih menjalankan asset frontend yang sebelumnya masih mengarah ke route redirect OAuth. Akibatnya, perilaku di browser tidak sama dengan source terbaru di server.

## 4. Kenapa Terasa "Kemarin Sudah Benar"
Ini terjadi karena ada perbedaan antara:

- **source code terbaru di server**, dan
- **bundle frontend yang masih tersimpan atau masih aktif di tab browser lama**.

Jadi secara teknis, sistem memang sudah diperbaiki, tetapi browser belum selalu mengambil asset baru. Karena itu pengguna masih melihat perilaku lama seolah-olah perbaikannya tidak bekerja.

## 5. Perbaikan yang Dilakukan
Perbaikan difokuskan pada halaman login Vue agar hanya memakai jalur yang stabil:

- Tombol Google dipastikan menggunakan **Google Identity Services credential flow**.
- Jalur paksa ke `route('auth.google')` dihapus dari interaksi tombol login.
- Mode GIS diset ke:
  - `ux_mode: 'popup'`
  - `use_fedcm_for_prompt: false`
- Sisa referensi redirect mode lama di template login dibersihkan.
- Asset frontend di-build ulang agar bundle production benar-benar memuat logika terbaru.

File utama yang diperbarui:
- [resources/js/Pages/Auth/Login.vue](/var/www/lawangsewu/resources/js/Pages/Auth/Login.vue:1)
- [resources/js/Pages/Auth/Login.interaction.test.js](/var/www/lawangsewu/resources/js/Pages/Auth/Login.interaction.test.js:1)

## 6. Verifikasi
Verifikasi dilakukan dengan beberapa cara:

- Memastikan halaman `/login` sudah melayani asset build terbaru.
- Memastikan bundle production tidak lagi mengandalkan jalur callback OAuth untuk tombol Google.
- Menjalankan test frontend pada interaksi halaman login.

Hasil test:

```text
✓ resources/js/Pages/Auth/Login.interaction.test.js (2 tests)
```

## 7. Hasil Akhir
Setelah frontend dibersihkan dan browser memuat asset terbaru, login Google kembali berhasil digunakan pada mode incognito.

## 8. Rekomendasi Ke Depan
- Jika perilaku login terasa "masih sama" setelah deploy, cek dulu apakah browser masih memakai tab atau asset lama.
- Untuk kasus serupa, gunakan URL baru lalu lakukan hard refresh atau buka incognito baru.
- Hindari mempertahankan dua alur login Google yang berbeda pada tombol yang sama, kecuali memang dibutuhkan dan dipisahkan dengan jelas.

