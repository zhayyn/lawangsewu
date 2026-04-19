# Integrasi Pilar Antrian PASMG ke Lawangsewu

## Kesimpulan

`pilarpasmg` **bisa** dimasukkan menjadi modul di Lawangsewu, dan pendekatan yang paling sesuai dengan blueprint adalah:

- **bukan** memindahkan mentah seluruh folder legacy
- **bukan** menjalankan dua otoritas antrean yang saling tumpang tindih
- tetapi menjadikannya **modul agregator antrean terpadu** di dalam Lawangsewu

Dengan pendekatan ini, Lawangsewu tetap menjadi **core shell + SSO + navigasi utama**, sementara Pilar Antrian PASMG menjadi **hub pelayanan antrean** yang menaungi:

- PTSP
- Sidang
- display publik
- operator workstation
- integrasi SIPP

## Dasar Keputusan

### Dari blueprint Lawangsewu

Blueprint Lawangsewu sudah mengarahkan ekosistem ke model:

- satu core
- banyak pintu layanan
- kontrol akses terpusat
- modul satelit yang dibungkus oleh Lawangsewu Core

### Dari blueprint pilarpasmg

Blueprint `pilarpasmg` juga sangat cocok dengan arah itu karena merekomendasikan:

- modular monolith
- satu authentication model
- satu queue authority
- satu deployment unit
- satu integration layer

Jadi secara arsitektur, keduanya **selaras**.

## Temuan Teknis Saat Ini

### Yang sudah ada di Lawangsewu

Saat ini Lawangsewu sudah memiliki fondasi:

- modul `Antrian PTSP`
- modul `Antrian Sidang`
- `SSO Google`
- role dan akses user
- shell dashboard dan navigasi internal

### Yang masih ada di pilarpasmg

Folder legacy `/var/www/pilarpasmg` masih menyimpan:

- PTSP legacy di `/var/www/pilarpasmg/ptsp`
- antrian sidang legacy di `/var/www/pilarpasmg/antrianpasmg`
- blueprint arsitektur dan kontrak API untuk target backend baru

Artinya:

- Lawangsewu sudah punya **fondasi modul aktif**
- pilarpasmg masih menjadi **sumber domain knowledge dan legacy migration source**

## Strategi Integrasi yang Dipilih

Strategi yang paling aman adalah **integrasi bertahap 4 fase**.

### Phase 1

Masukkan Pilar Antrian PASMG sebagai modul resmi di Lawangsewu.

Tujuan:

- Pilar Antrian PASMG muncul di menu
- punya halaman sendiri
- menjadi hub konseptual untuk antrean terpadu
- mengarahkan user ke modul PTSP dan Sidang yang sudah aktif

### Phase 2

Samakan domain data antrean.

Target:

- satu katalog layanan
- satu katalog loket/ruang
- satu model transisi status
- fondasi menuju `queue authority`

### Phase 3

Migrasikan display publik, audio panggil, dan printer payload.

Target:

- TV antrean
- audio call
- kiosk/self-service
- cetak tiket

### Phase 4

Kunci integrasi SIPP melalui adapter resmi.

Target:

- jadwal sidang
- relasi perkara
- ruang sidang
- sinkronisasi terkendali tanpa query liar dari UI

## Yang Sudah Dikerjakan Hari Ini

Pilar Antrian PASMG sudah dimasukkan ke Lawangsewu sebagai modul baru:

- route baru: `/pilar-smg`
- controller baru via `PortalController@pilar`
- menu sidebar dan shortcut dashboard
- halaman modul baru `Pilar.vue`
- test akses dasar

File utama:

- [PortalController.php](/var/www/lawangsewu/app/Http/Controllers/PortalController.php)
- [LawangsewuPortal.php](/var/www/lawangsewu/app/Support/LawangsewuPortal.php)
- [web.php](/var/www/lawangsewu/routes/web.php)
- [Pilar.vue](/var/www/lawangsewu/resources/js/Pages/Lawangsewu/Pilar.vue)
- [PilarModuleTest.php](/var/www/lawangsewu/tests/Feature/Portal/PilarModuleTest.php)

## Rekomendasi Lanjutan

Langkah berikutnya yang paling masuk akal adalah:

1. buat `service catalog` Pilar Antrian PASMG di database Lawangsewu
2. buat `counter catalog` untuk loket dan ruang sidang
3. samakan status antrean PTSP dan Sidang ke state machine yang konsisten
4. pindahkan display calling dari legacy ke modul inti
5. hubungkan data sidang ke SIPP adapter resmi

## Catatan Penting

Kalau tujuan akhirnya adalah sistem antrean terpadu yang sehat, maka:

- legacy `pilarpasmg` harus diperlakukan sebagai **sumber migrasi**
- bukan sebagai sistem kedua yang terus hidup paralel tanpa batas

Kalau dua otoritas antrean dibiarkan hidup berdampingan terlalu lama, nanti akan muncul:

- nomor antre ganda
- status berbeda antar layar
- operator bingung bekerja di dua tempat
- sulit audit dan sulit maintenance

Karena itu keputusan yang tepat adalah:

**Pilar Antrian PASMG masuk ke Lawangsewu sebagai modul, lalu legacy diserap bertahap sampai queue authority hanya satu.**
