# Laporan Audit Strategis: Urgensi dan Manfaat Implementasi SSO pada Ekosistem Digital Lawangsewu

**Tanggal Audit:** 3 Mei 2026
**Fokus Audit:** Arsitektur Otentikasi dan Identitas Pengguna (Identity & Access Management)
**Sistem Tinjauan:** Ekosistem Lawangsewu (Modul Inti, WaCaraka, Buku Tamu, Interkom, Mas-Satset)

---

## 1. Ringkasan Eksekutif (Executive Summary)
Pertanyaan mendasar sering muncul dalam pengembangan sistem terpadu: *"Jika seluruh aplikasi dan modul sudah tergabung dalam satu ekosistem (Satu Kesatuan Portal), apakah Single Sign-On (SSO) masih memiliki nilai guna?"*

Hasil audit arsitektur menyimpulkan bahwa **SSO tidak hanya bermanfaat, melainkan sangat krusial**. Meskipun di sisi *frontend* (antarmuka pengguna) Lawangsewu terlihat sebagai satu kesatuan, di sisi *backend* (infrastruktur), ekosistem ini terdiri dari berbagai teknologi dan layanan yang beroperasi secara independen (contoh: Core berbasis Laravel/Vue, terintegrasi dengan modul berbasis WordPress seperti Mas-Satset). SSO adalah jembatan tak terlihat yang menjaga ilusi "satu kesatuan" tersebut tetap utuh.

## 2. Perbandingan Komprehensif: Login Biasa vs SSO dalam Ekosistem Terpadu

| Aspek Penilaian | Login Konvensional (Regular Login) | Single Sign-On (SSO) |
| :--- | :--- | :--- |
| **Beban Pengguna (UX)** | Pengguna harus *login* ulang setiap kali mengakses modul yang di-*hosting* di *sub-domain* atau *platform* berbeda, memecah pengalaman pengguna. | Otentikasi terjadi **satu kali**. Token otentikasi didistribusikan secara *seamless* ke seluruh sistem di dalam ekosistem. |
| **Manajemen Kredensial** | Setiap aplikasi berpotensi memiliki tabel *database user* sendiri. Jika *user* mengganti *password* di portal A, *password* di portal B belum tentu berubah. | Hanya ada satu sumber kebenaran data (*Single Source of Truth*). Perubahan *password* atau profil otomatis berlaku secara global di seluruh ekosistem. |
| **Keamanan & Pencabutan Akses (Revocation)** | Sangat berisiko. Jika pegawai *resign* atau bermasalah, Administrator harus mencari dan mencabut akses *user* tersebut satu per satu di setiap modul/aplikasi. Risiko *human-error* tinggi. | **Kendali Tersentralisasi**. Administrator cukup menonaktifkan akun di satu titik (pusat kontrol). Secara *real-time*, akses orang tersebut ke seluruh ekosistem akan tertutup mati. |
| **Audit Trail (Log Aktivitas)** | Jejak keamanan tersebar. Sangat sulit merekonstruksi kapan dan di mana saja *user* tersebut berpindah dalam satu hari. | Portal SSO mencatat seluruh distribusi sesi. Admin dapat memantau dengan tepat pergerakan keluar-masuk pengguna antar modul. |
| **Skalabilitas Masa Depan** | Jika ada instruksi penambahan aplikasi pihak ketiga baru, *developer* harus membangun sistem integrasi *login* dari nol (di-*hardcode*). | Sistem baru tinggal "dicolokkan" ke protokol standar SSO (seperti OAuth2/SAML) tanpa perlu membuat sistem *user* dan otentikasi baru. |

## 3. Analisis Teknis Kasus "Lawangsewu"
Pada Lawangsewu, pendekatan "Satu Kesatuan" adalah sebuah pencapaian secara operasional. Namun, secara arsitektur teknologi:
1.  **Heterogenitas Teknologi:** Terdapat percampuran *stack* teknologi (Laravel, Vue, PHP murni, WordPress). Tiap *framework* memiliki cara sendiri dalam mengelola *cookie* dan sesi. Tanpa SSO, sesi *login* Laravel tidak akan dikenali oleh WordPress.
2.  **Isolasi Kegagalan (Fault Isolation):** Modul dirancang secara terpisah agar jika satu modul tumbang, modul lain tetap jalan. Otentikasi pun harus dipisah (SSO bertindak sebagai *Identity Provider* independen).

## 4. Kesimpulan dan Rekomendasi
Implementasi SSO pada Lawangsewu **bukanlah sebuah redundansi (pemborosan), melainkan sebuah esensi stabilitas**. 

SSO adalah elemen yang mengubah kumpulan modul-modul independen menjadi sebuah **Ekosistem Digital Sejati** yang aman, efisien bagi Administrator, dan nyaman tanpa hambatan (*frictionless*) bagi pengguna akhir. Kami merekomendasikan agar seluruh rute dan akses modul selalu divalidasi melalui token terpusat (SSO) ini.
