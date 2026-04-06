# Report Migrasi Detail Lawangsewu

Tanggal acuan: 2026-03-15

Jika tidak butuh laporan penuh, mulai dulu dari `MIGRATION-ONEPAGE-LAWANGSEWU.md`.

## Status Resmi Saat Ini

Lawangsewu sekarang sudah resmi diposisikan sebagai `CI4 Core` pada level arsitektur.

Makna kalimat di atas harus dibaca tepat:

- resmi sebagai rumah utama arsitektur: `ya`
- resmi sebagai runtime produksi final tunggal yang sudah cutover penuh: `belum`

Jadi, per 2026-03-15, keputusan besarnya sudah final:

- Lawangsewu = CI4 Core
- WA Caraka = sibling app
- Laravel = sibling app lane bisnis

Tetapi migrasi operasionalnya masih bertahap, terutama untuk cutover final dan penutupan compatibility layer.

## Ringkasan Eksekutif

Hal-hal yang sudah berhasil dicapai:

- boundary arsitektur besar sudah dibekukan
- shell staging CI4 Core sudah hidup dan dipakai untuk rollout bertahap
- family publik utama, portal, dan app registry sudah masuk shadow aktif tervalidasi
- portal gateway sudah dirapikan ke pola SSO portal-sentris
- widget source tree sudah direorganisasi agar lebih jelas antara public, api, system, data, dan config
- dokumentasi walkthrough, route inventory, systems map, dan security ops sudah dipublish
- starter Laravel sibling app nyata sudah dibentuk
- domain bisnis pertama untuk Laravel sudah dipilih: `helpdesk/ticketing internal`
- modul awal helpdesk Laravel sudah tersedia dan tervalidasi pada mode persistence transisi
- subproject `wa-caraka` sudah dipublish ulang untuk batch source/docs aman

Hal-hal yang belum boleh diklaim selesai:

- CI4 Core belum menjadi runtime final tunggal untuk seluruh Lawangsewu
- shadow route belum semuanya dinaikkan menjadi cutover final
- Laravel sibling app belum punya database final dan SSO trust final
- compatibility route lama masih harus dipertahankan
- verifikasi visual dan verifikasi integrasi nyata masih harus diteruskan

## Posisi Arsitektur yang Berlaku

### 1. Lawangsewu sebagai CI4 Core

Peran resmi Lawangsewu saat ini:

- landing page utama
- portal utama
- auth gateway dan logout
- launcher ke sibling app
- registry aplikasi
- registry widget
- registry dokumentasi
- monitoring dan audit ringan

Peran yang sengaja tidak dipaksa masuk ke CI4 Core:

- runtime WA Caraka
- dashboard admin WA Caraka
- aplikasi bisnis besar dengan workflow sendiri

### 2. WA Caraka sebagai sibling app

WA Caraka tetap dipisah dari CI4 Core.

Artinya:

- runtime Node tetap hidup sebagai service terpisah
- dashboard admin CI4 WA Caraka tetap menjadi aplikasi saudara, bukan modul portal
- integrasi dilakukan lewat launcher, wrapper, dan SSO portal

Update terbaru yang sudah dipublish di subproject WA Caraka:

- login langsung lama dinonaktifkan dan diarahkan ke portal Lawangsewu
- halaman redirect SSO eksplisit ditambahkan
- message history diberi audit metadata AI mode/source/model
- dokumentasi runtime, logs, scripts, dan dashboard docs dirapikan
- script start/stop/status menyesuaikan runtime lock dan observability ringan
- server runtime sudah menegaskan mode AI `prompt-only`, `rag-connected`, `db-api-connected`, dan `auto-hybrid`
- file runtime lokal yang sebelumnya terus membuat worktree kotor sekarang sudah dihentikan dari version control

Yang sengaja belum dipublish dari subproject WA Caraka:

- file runtime state lokal
- report intent mingguan terbaru

## Status Family Route

### Family yang sudah punya jalur shadow aktif tervalidasi

- `/`
- `/walkthrough`
- `/daftar-widget`
- family public widget
- `/portal`
- `portal/launch`
- app registry

### Family yang sudah terbukti aman secara teknis pada fase sekarang

- route publik kanonik utama bisa merespons normal
- alias kompatibilitas tetap hidup dengan `302` atau mapping yang sesuai
- gap live yang sebelumnya `404` pada beberapa route portal/app registry sudah tertutup oleh shadow staging

### Family yang tetap harus diperlakukan hati-hati

- route auth lintas aplikasi
- route API bridge yang masih melayani kompatibilitas lama
- utility route dan route system internal

## Status Route Inventory

Kontrak route yang tetap wajib dijaga sampai migrasi benar-benar selesai:

- `/`
- `/portal`
- `/walkthrough`
- `/daftar-widget`
- seluruh route gateway login/logout/launcher di `/lawangsewu/gateway/*`
- route widget publik dan compatibility alias yang sudah digunakan user, bookmark, iframe, atau integrasi lama
- kontrak integrasi WA Caraka seperti `/wa-caraka-admin/index.php/sso-login`

Kesimpulan praktis untuk route:

- URL publik tidak boleh diputus sembarangan
- alias lama harus tetap dijaga sampai benar-benar tidak dibutuhkan
- utility internal jangan tiba-tiba dibuka sebagai publik baru

## Status Gateway dan Portal

Perubahan penting yang sudah dipublish pada root repo:

- gateway login sekarang diarahkan tegas ke portal-sentris
- logout tidak lagi mengandalkan redirect rapuh ke aplikasi lain
- ada normalisasi return path
- ada service map SSO di gateway
- role enforcement dasar sudah tersedia
- halaman portal menampilkan launcher yang lebih konsisten dengan SSO

Makna operasionalnya:

- identitas masuk utama kini makin jelas di portal Lawangsewu
- aplikasi saudara menerima akses lewat pola trust dari portal, bukan dari banyak pintu login mandiri

## Status Laravel Sibling App

### Yang sudah selesai

Starter Laravel nyata sudah tersedia di:

- `projects/lawangsewu-business-laravel/`

Yang sudah ada di starter tersebut:

- onboarding route `/`
- health route `/health`
- config khusus Lawangsewu
- penegasan boundary portal vs business app
- domain bisnis pertama: `helpdesk/ticketing internal`
- modul awal helpdesk:
  - `/helpdesk/tickets`
  - `/helpdesk/tickets/create`

### Kenapa domain pertama dipilih helpdesk

Karena helpdesk internal memenuhi syarat app Laravel yang benar:

- punya workflow status
- punya prioritas
- bisa berkembang ke assignment, SLA, dashboard, reporting, dan notifikasi
- cukup besar untuk dipisah dari portal
- tidak mencampur fungsi rumah utama CI4 Core

### Kondisi runtime Laravel saat ini

Starter Laravel saat ini berjalan pada mode transisi untuk persistence.

Fakta server saat ini:

- `pdo_sqlite` tidak tersedia di PHP CLI
- user MySQL yang tersedia tidak punya privilege membuat database baru untuk app Laravel

Karena itu modul helpdesk awal memakai mode `staged-file` sebagai persistence transisi.

Yang penting:

- ini bukan solusi akhir database
- ini solusi transisi agar lane bisnis Laravel benar-benar hidup dan bisa diuji tanpa menempel ke database sistem lain
- model dan migration database final tetap sudah disiapkan

### Yang belum selesai di Laravel

- database final khusus Laravel
- provisioning user dan privilege database final
- middleware trust atau signed token dari portal
- launcher final dari CI4 Core ke Laravel app
- modul bisnis berikutnya setelah helpdesk

## Status Dokumentasi dan Paket Hasil

Paket dokumentasi yang sudah dipublish dan relevan:

- blueprint migrasi CI4/Laravel
- migration matrix
- route inventory
- CI4 module map
- executive status
- systems map
- walkthrough bundle
- dokumen security/remote access
- onboarding Laravel sibling app

Artinya, repo sekarang tidak hanya berisi implementasi, tetapi juga landasan pengambilan keputusan yang cukup lengkap.

## Timeline Commit Penting Terbaru

Urutan milestone terbaru yang paling relevan:

- `e0b40c91c` `docs: update executive status after public shadow activation`
- `9ed184a01` `feat: add portal and app registry shadow toggles`
- `deadf42f5` `feat: activate portal and app registry shadow`
- `4a6d6a2c9` `feat: snapshot ci4 staging scaffold`
- `e434d8243` `refactor: reorganize widget source tree`
- `9085d9c3f` `feat: add migration support assets`
- `d70754b22` `docs: add walkthrough and route inventory bundle`
- `6b51e3b19` `docs: add operational support assets`
- `4836c83d1` `feat: refine portal gateway and archive legacy page`
- `d3d9f8024` `feat: bootstrap laravel sibling app starter`
- `551bb624f` `feat: add laravel helpdesk starter domain`
- `e11093aa5` `chore: update wa-caraka subproject pointer`
- `e79739a91` `chore: update wa-caraka subproject pointer`

## Risiko yang Masih Ada

Risiko utama jika status sekarang dibaca terlalu optimistis:

- orang mengira CI4 sudah menjadi runtime final tunggal padahal belum
- orang mengira Laravel business app sudah siap produksi padahal baru starter lane bisnis
- compatibility route diputus terlalu cepat
- sibling app disalahpahami seolah sudah selesai diintegrasikan penuh
- AI mode `db-api-connected` dianggap live padahal belum tentu endpoint dan data source finalnya benar-benar siap

## Catatan Penting Soal AI Capability

Untuk semua area AI, label capability tetap wajib diverifikasi sebelum rollout atau training:

- `prompt-only`
- `DB/API-connected`
- `RAG-connected`
- `hybrid`

Khusus WA Caraka, dokumentasi dan runtime sekarang sudah lebih tegas membedakan mode ini.

Namun itu tidak otomatis berarti semua mode sudah siap diumumkan sebagai fitur produksi live-data.

## Sisa Kondisi Worktree yang Masih Perlu Diketahui

Root repo sekarang sudah terpublish untuk batch migrasi yang dikerjakan.

Setelah cleanup final `wa-caraka`, kondisi praktis worktree adalah:

- repo utama sudah sinkron untuk seluruh target migrasi aktif
- subproject `wa-caraka` tidak lagi kotor oleh file runtime lokal yang memang seharusnya tidak dilacak git
- satu-satunya dirty marker yang tersisa di root adalah archive legacy `archive/whatsapp-legacy-20260306/senopati-api-v1/ci4-app`

Makna operasionalnya:

- pekerjaan migrasi aktif pada batch ini bisa dianggap selesai aman untuk dipublish
- archive legacy tetap sengaja dibiarkan pasif dan tidak dinormalisasi sembarangan
- jika ingin langkah berikutnya, fokusnya bukan lagi cleanup dasar, tetapi integrasi final per family atau trust boundary sibling app
- dirty marker root yang tersisa sekarang hanya archive legacy pasif

Audit read-only terbaru pada archive legacy menunjukkan:

- repo `archive/whatsapp-legacy-20260306/senopati-api-v1/ci4-app` masih berupa CI4 starter legacy yang dekat dengan `appstarter`
- custom yang tampak hanya tipis di layer WA V1 command center
- dirty state yang terdeteksi saat ini berasal dari `.gitignore` yang berubah dan `README.md` yang terhapus di dalam repo arsip itu

Kesimpulan praktisnya tetap sama:

- arsip ini tidak perlu disentuh untuk menutup batch migrasi aktif
- dokumentasi status lebih aman daripada normalisasi agresif pada repo arsip

## Kesimpulan Final yang Paling Akurat

Kalimat paling akurat untuk ditetapkan sebagai status resmi saat ini adalah:

Lawangsewu sudah resmi menjadi `CI4 Core` sebagai rumah utama arsitektur, tetapi migrasi operasionalnya belum selesai penuh sebagai cutover produksi final tunggal.

WA Caraka tetap sibling app yang sudah makin rapi integrasinya ke portal.

Laravel sibling app sudah naik dari blueprint menjadi starter nyata, dan lane bisnis pertamanya sudah dipilih serta dibentuk sebagai `helpdesk/ticketing internal`, tetapi masih berada pada fase onboarding, bukan fase produksi final.

## Langkah Lanjutan yang Paling Masuk Akal

1. Provision database final untuk Laravel sibling app.
2. Pasang middleware trust atau signed token dari portal ke Laravel.
3. Naikkan route family dari shadow ke cutover final hanya berdasarkan bukti teknis, bukan asumsi.
4. Bersihkan sisa runtime-local files pada subproject WA Caraka tanpa mencampur source dan output runtime.
5. Biarkan repo arsip lama tetap pasif sampai ada keputusan eksplisit untuk normalisasi atau pembekuan ulang.