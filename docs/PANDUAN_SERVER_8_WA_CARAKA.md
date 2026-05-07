1231# Panduan Lengkap Pemanfaatan Server `192.168.88.33` untuk WA Caraka

Tanggal: 2026-04-23

## Ringkasan Singkat

Ya, server `192.168.88.33` sangat bisa dimanfaatkan.

Ada 2 model utama:

1. `Model A - Dipisah`
   Lawangsewu tetap di server sekarang, sedangkan `wa-runtime` pindah ke server `192.168.88.33`.

2. `Model B - Disatukan di server 33`
   Seluruh Lawangsewu termasuk WA Caraka, Laravel, web server, dan `wa-runtime` dipindah ke `192.168.88.33`.

Rekomendasi saya:

- Untuk stabilitas dan keamanan: pilih `Model A - Dipisah`.
- Untuk sederhana dan hemat pengelolaan: `Model B` bisa dipakai, tapi perlu disiplin resource dan backup.

Kalau server `192.168.88.33` punya:

- RAM `16 GB`
- Xeon `X99`
- sudah ada `Ollama 3B`

maka:

- `16 GB` masih cukup untuk `wa-runtime` saja dengan sangat aman.
- `16 GB` juga masih cukup untuk `Lawangsewu + wa-runtime + Ollama 3B`, tapi itu sudah masuk wilayah "cukup, bukan longgar".
- Kalau trafik naik, ada indexing, build, backup, atau query berat, server gabungan akan lebih mudah sesak daripada model terpisah.

## Analogi Sederhana

Bayangkan sistem Anda seperti kantor pelayanan.

- `Laravel / Lawangsewu` adalah gedung utama pelayanan.
- `wa-runtime` adalah operator telepon / call center.
- `database` adalah lemari arsip.
- `Ollama 3B` adalah staf analis yang kadang diminta berpikir berat.

### Model A: Dipisah

Ini seperti:

- gedung pelayanan tetap di kantor utama
- call center dipindah ke gedung sebelah

Keuntungannya:

- kalau call center sedang ramai atau rusak, pelayanan loket tetap jalan
- kalau telepon error, arsip dan front desk tidak ikut kacau
- lebih mudah membatasi siapa yang boleh masuk ke ruang call center

### Model B: Disatukan

Ini seperti:

- front desk
- call center
- arsip
- staf analis

semua ada dalam satu gedung.

Keuntungannya:

- lebih sederhana
- lebih mudah dipantau dari satu tempat

Tapi risikonya:

- kalau call center ramai, front desk ikut melambat
- kalau staf analis memakai banyak tenaga listrik, semua ruangan terasa berat
- kalau satu gedung bermasalah, semua layanan terdampak sekaligus

## Perbandingan Dua Model

| Aspek | Model A: Lawangsewu tetap, `wa-runtime` pindah ke `192.168.88.33` | Model B: semua pindah ke `192.168.88.33` |
|---|---|---|
| Stabilitas web utama | Lebih tinggi | Sedang |
| Isolasi gangguan WA | Sangat baik | Rendah |
| Keamanan | Lebih baik | Cukup, tapi lebih banyak risiko satu titik |
| Kemudahan setup awal | Sedang | Paling mudah secara konsep |
| Kemudahan maintenance | Baik | Baik di awal, lebih rumit saat beban naik |
| Risiko single point of failure | Lebih kecil | Lebih besar |
| Cocok untuk pertumbuhan trafik | Lebih cocok | Lebih terbatas |
| Kebutuhan resource di server 33 | Ringan | Lebih berat |
| Dampak Ollama ke sistem | Minimal | Lebih terasa |

## Rekomendasi Saya

### Rekomendasi Utama

Pilih `Model A - Dipisah`.

Susunannya:

- Server lama:
  - Nginx
  - Laravel Lawangsewu
  - database utama
  - queue worker Laravel

- Server `192.168.88.33`:
  - `wa-runtime`
  - session WhatsApp
  - log runtime
  - opsional reverse proxy internal

Alasannya:

1. masalah WA paling sering ada di layer runtime, session device, QR, reconnect, atau media
2. masalah-masalah itu lebih aman kalau dipenjara di mesin terpisah
3. kalau suatu hari WA crash, minimal portal utama tidak ikut tumbang
4. RAM `16 GB` lebih dari cukup untuk kerja khusus runtime WA

### Rekomendasi Kedua

Kalau Anda ingin sederhana dulu dan cepat migrasi, `Model B` masih bisa dipakai bila:

- traffic user tidak tinggi
- Ollama 3B tidak aktif terus-menerus
- tidak ada job berat bersamaan
- database tidak terlalu besar
- Anda siap memonitor RAM, CPU, dan swap

## Apakah RAM 16 GB Cukup?

### Jawaban Praktis

`Cukup`, tapi konteksnya penting.

### Kalau server 33 hanya untuk `wa-runtime`

`16 GB` sangat cukup.

Perkiraan kasar:

- Ubuntu server: `1 - 2 GB`
- Node `wa-runtime`: `300 MB - 1.5 GB` tergantung traffic, media, dan cache
- log / buffer / system overhead: `1 - 2 GB`

Masih sangat longgar.

### Kalau server 33 menampung semuanya

Komponen yang berbagi RAM:

- Ubuntu
- Nginx
- PHP-FPM
- Laravel app
- MariaDB/MySQL jika ikut pindah
- `wa-runtime`
- queue worker
- Ollama 3B

Perkiraan kasar real-world:

- Ubuntu: `1.5 GB`
- Nginx + PHP-FPM + Laravel idle-menengah: `1.5 - 4 GB`
- MariaDB/MySQL: `2 - 6 GB`
- `wa-runtime`: `0.5 - 1.5 GB`
- Ollama 3B: `2 - 4+ GB` tergantung model load, context, dan concurrency

Total bisa cepat masuk ke area `8 - 14 GB`.

Itu artinya:

- masih bisa jalan
- tapi ruang napas tidak besar
- saat spike, system bisa mulai swap

Kalau sudah swap:

- WA terasa lambat
- Laravel bisa lambat
- database terasa berat
- Ollama bisa mengganggu layanan utama

## Pendapat Khusus tentang Ollama 3B

Ollama 3B itu bukan monster, tapi tetap punya kebiasaan "makan tempat".

Analogi:

- `wa-runtime` itu petugas telepon yang butuh meja kecil
- Laravel itu loket pelayanan
- database itu lemari arsip besar
- Ollama itu staf analis yang butuh meja lebar, banyak kertas, dan kadang memonopoli ruangan

Kalau semua disatukan, bukan karena Ollama jahat, tapi karena pola kerjanya berbeda:

- kadang idle
- tapi saat aktif, dia bisa menyedot RAM dan CPU cukup terasa

Kalau `Ollama 3B` memang ingin tetap ada di server 33, maka saya makin menyarankan:

- `server 33` fokus untuk WA runtime saja
- atau
- kalau semua dipindah ke server 33, beri batas resource yang ketat ke Ollama

## Rekomendasi Final Berdasarkan Kondisi Anda

Dengan kondisi:

- ada server Ubuntu lain di `192.168.88.33`
- RAM `16 GB`
- Xeon `X99`
- sudah ada `Ollama 3B`

rekomendasi terbaik saya adalah:

### Opsi Terbaik

- Biarkan Lawangsewu tetap di server sekarang.
- Pindahkan hanya `wa-runtime` ke `192.168.88.33`.
- Biarkan Ollama tetap di server 33 jika memang harus, tapi jangan gabungkan seluruh aplikasi Lawangsewu di sana.

Ini memberi kombinasi terbaik antara:

- aman
- stabil
- hemat
- tidak terlalu ribet

### Opsi "Boleh, tapi hati-hati"

Pindahkan semua ke server 33 hanya jika:

- Anda ingin konsolidasi mesin
- beban user masih kecil sampai sedang
- Anda siap membatasi Ollama
- Anda siap memonitor resource setiap hari

Kalau tidak, Anda sedang menaruh:

- portal
- WA
- database
- AI local

di satu titik yang sama.

Itu nyaman di awal, tapi paling riskan kalau ada lonjakan atau satu service bermasalah.

## Arsitektur yang Saya Sarankan

### Model A - Disarankan

```text
User Browser
   ->
Nginx + Laravel (server utama)
   ->
Database utama
   ->
WA Caraka Controller / Service
   ->
Private LAN 192.168.88.33:8790
   ->
wa-runtime
   ->
WhatsApp device session

Ollama 3B
   ->
tetap di server 33, tapi terpisah dari jalur utama web
```

### Model B - Bisa, tapi lebih padat

```text
User Browser
   ->
Nginx
   ->
Laravel + PHP-FPM
   ->
Database
   ->
wa-runtime
   ->
Ollama 3B

semua hidup dalam satu mesin 192.168.88.33
```

## Panduan Implementasi Lengkap untuk Model A

## Tahap 1 - Siapkan server `192.168.88.33`

Tujuan:

- buat server 33 menjadi "ruang khusus WA"

Checklist:

1. update package
2. install Node.js LTS
3. buat user khusus misalnya `wacaraka`
4. buat folder aplikasi
5. pindahkan `wa-runtime`
6. pasang service manager
7. buka firewall hanya untuk server Laravel

Contoh:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl git ufw
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt install -y nodejs
sudo useradd -r -m -d /opt/wacaraka -s /bin/bash wacaraka
sudo mkdir -p /opt/wacaraka/wa-runtime
sudo chown -R wacaraka:wacaraka /opt/wacaraka
```

## Tahap 2 - Salin `wa-runtime` ke server 33

Contoh dari server utama:

```bash
rsync -avz /var/www/lawangsewu/wa-runtime/ user@192.168.88.33:/opt/wacaraka/wa-runtime/
```

Lalu di server 33:

```bash
cd /opt/wacaraka/wa-runtime
npm install
```

## Tahap 3 - Buat file environment runtime

Kalau `wa-runtime` memakai env, simpan terpisah.

Contoh konsep:

```env
PORT=8790
LW_WA_V2_TOKEN=ganti_dengan_token_panjang_acak
NODE_ENV=production
```

## Tahap 4 - Jalankan dengan `systemd`

Buat file:

`/etc/systemd/system/wacaraka-runtime.service`

```ini
[Unit]
Description=WA Caraka Runtime
After=network.target

[Service]
Type=simple
User=wacaraka
WorkingDirectory=/opt/wacaraka/wa-runtime
Environment=PORT=8790
Environment=NODE_ENV=production
Environment=LW_WA_V2_TOKEN=ganti_dengan_token_panjang_acak
ExecStart=/usr/bin/node server.mjs
Restart=always
RestartSec=5
StandardOutput=append:/var/log/wacaraka-runtime.log
StandardError=append:/var/log/wacaraka-runtime-error.log

[Install]
WantedBy=multi-user.target
```

Aktifkan:

```bash
sudo systemctl daemon-reload
sudo systemctl enable wacaraka-runtime
sudo systemctl start wacaraka-runtime
sudo systemctl status wacaraka-runtime
```

## Tahap 5 - Kunci firewall server 33

Analogi:

- jangan jadikan ruang call center punya pintu dari jalan raya
- cukup buka pintu dari kantor utama saja

Contoh:

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow from IP_SERVER_LARAVEL to any port 8790
sudo ufw enable
sudo ufw status
```

Ganti `IP_SERVER_LARAVEL` dengan IP LAN server utama.

## Tahap 6 - Ubah Laravel di server utama

Edit `.env` di server Lawangsewu:

```env
LW_WA_V2_BASE=http://192.168.88.33:8790
LW_WA_V2_TOKEN=ganti_dengan_token_yang_sama
LW_WA_V2_TIMEOUT=20
```

Lalu refresh config:

```bash
cd /var/www/lawangsewu
php artisan optimize:clear
```

## Tahap 7 - Uji koneksi

Tes dari server utama:

```bash
curl -H "X-WA-V2-Token: ganti_dengan_token_yang_sama" http://192.168.88.33:8790/health
```

Kalau sehat, baru tes dari UI `/wa-caraka`.

## Tahap 8 - Lindungi session WhatsApp

Yang paling berharga dari runtime WA biasanya:

- session auth
- state device
- token media sementara

Maka:

- simpan di folder yang bukan public
- permission ketat
- backup berkala

Contoh:

```bash
sudo chown -R wacaraka:wacaraka /opt/wacaraka
sudo chmod -R 750 /opt/wacaraka
```

## Tahap 9 - Monitoring minimum

Pasang monitoring untuk:

- RAM
- CPU
- disk
- status service `wacaraka-runtime`
- response `/health`

Minimal cek:

```bash
systemctl status wacaraka-runtime
free -h
df -h
top
```

## Tahap 10 - Backup

Backup minimal:

- folder session runtime
- file env runtime
- database Lawangsewu

Analogi:

- WA runtime tanpa backup session itu seperti call center tanpa kunci cadangan

## Panduan Implementasi Lengkap untuk Model B

Kalau Anda tetap ingin semua pindah ke server 33, maka lakukan ini:

1. pasang Nginx
2. pasang PHP + PHP-FPM
3. pasang database
4. pindah repo Lawangsewu
5. pindah `wa-runtime`
6. buat service terpisah untuk:
   - nginx
   - php-fpm
   - mysql/mariadb
   - queue worker
   - wa-runtime
   - ollama
7. atur limit resource

### Hal yang wajib kalau semua digabung

- aktifkan swap secukupnya
- batasi memory Ollama bila memungkinkan
- batasi jumlah worker PHP-FPM
- buat log rotate
- jangan jalankan build berat bersamaan jam operasional

## Konfigurasi Operasional yang Saya Sarankan Bila Semua Digabung

### PHP-FPM

Jangan terlalu agresif.

Karena kalau `pm.max_children` terlalu besar:

- web memang terlihat siap
- tapi RAM cepat habis

### Database

Jangan set buffer terlalu besar.

Untuk mesin 16 GB yang juga menampung AI dan runtime lain, lebih baik konservatif.

### Ollama

Kalau tidak dipakai terus-menerus:

- jangan biarkan dia selalu aktif memakan RAM besar
- jadikan service yang dinyalakan saat perlu

### Queue

Laravel worker jangan terlalu banyak.

Sedikit tapi stabil lebih baik daripada banyak tapi saling berebut RAM.

## Kapan Harus Memilih Model B?

Pilih semua pindah ke server 33 hanya jika:

- server lama mau dipensiunkan
- ingin satu titik administrasi
- user aktif tidak terlalu banyak
- siap menerima risiko single point of failure

## Kapan Harus Tetap Model A?

Tetap pilih model terpisah jika:

- WA Caraka dianggap layanan penting
- portal utama tidak boleh ikut terganggu
- Anda sudah punya server kedua
- Anda ingin langkah aman tanpa beli mesin baru

Itu persis kondisi Anda sekarang.

Jadi sekali lagi, model terpisah adalah pilihan paling sehat.

## Keputusan Praktis yang Saya Sarankan

### Pilihan yang saya pilih kalau ini server saya sendiri

Saya akan lakukan ini:

1. Server utama tetap jadi rumah Lawangsewu.
2. Server `192.168.88.33` dijadikan rumah `wa-runtime`.
3. Ollama tetap di server 33, tapi jangan dicampur jalur utama web.
4. Database tetap di server utama dulu.
5. Setelah stabil, baru evaluasi apakah perlu migrasi penuh.

Alasannya:

- perubahan lebih kecil
- downtime lebih kecil
- rollback lebih mudah
- hasil stabilitas paling besar

## Checklist Keputusan Cepat

Kalau jawaban Anda lebih banyak "ya" di bawah ini, pilih `Model A`:

- Apakah portal utama harus tetap jalan meski WA error?
- Apakah Anda ingin risiko lebih kecil?
- Apakah server 33 sudah tersedia?
- Apakah Ollama tetap ingin dipakai?

Kalau jawaban Anda lebih banyak "ya" di bawah ini, `Model B` bisa dipertimbangkan:

- Apakah Anda ingin satu server saja?
- Apakah trafik masih kecil?
- Apakah Anda siap monitor resource ketat?
- Apakah risiko satu titik gagal masih bisa diterima?

## Penutup

Kesimpulan final:

- `192.168.88.33` sangat layak dimanfaatkan.
- Untuk kondisi Anda, pilihan paling bijak adalah:
  `pindahkan hanya wa-runtime ke server 33`.
- RAM `16 GB` cukup untuk itu dengan aman.
- RAM `16 GB` juga masih bisa untuk semua digabung, tapi saya tidak merekomendasikannya sebagai pilihan pertama karena di sana juga sudah ada `Ollama 3B`.

Kalau ingin, langkah berikutnya yang paling masuk akal adalah saya bantu siapkan dokumen tahap 2:

- file `systemd`
- rule `ufw`
- template `.env`
- checklist cutover
- rollback plan

agar Anda bisa langsung eksekusi migrasi ke server `192.168.88.33`.
