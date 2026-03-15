# Route Inventory Lawangsewu

Dokumen ini membekukan kontrak route yang wajib dipertahankan saat Lawangsewu dimigrasikan ke CI4 Core.

Prinsip utama:

- route publik yang sudah hidup tidak boleh putus tanpa compatibility layer
- route auth harus stabil
- alias lama tetap dijaga sampai migrasi benar-benar selesai

## 1. Route Inti Rumah Lawangsewu

Route berikut adalah pintu utama dan harus dipertahankan.

| Route | Fungsi Saat Ini | Prioritas |
|---|---|---|
| `/` | landing Lawangsewu | wajib keep |
| `/portal` | portal utama sesudah login | wajib keep |
| `/walkthrough` | dokumentasi publik | wajib keep |
| `/daftar-widget` | katalog widget publik | wajib keep |

## 2. Route Auth dan Gateway

Route berikut adalah kontrak akses lintas sistem.

| Route | Fungsi Saat Ini | Prioritas |
|---|---|---|
| `/lawangsewu/gateway/index` | UI utama gateway | keep selama transisi |
| `/lawangsewu/gateway/login` | login portal/gateway | wajib keep |
| `/lawangsewu/gateway/logout` | logout portal/gateway | wajib keep |
| `/lawangsewu/gateway/dubes-prakom` | ops/launcher Dubes Prakom | keep |
| `/lawangsewu/gateway/mas-satset-ai` | lab knowledge dan uji chat | keep |
| `/lawangsewu/gateway/sso-mapping` | peta layanan SSO | keep |

Catatan:

- route auth di atas boleh nanti dipetakan ke CI4 Core, tetapi URL publiknya jangan berubah sembarangan
- logout tidak boleh lagi bergantung pada redirect rapuh ke aplikasi lain

## 3. Route Publik Widget dan Dashboard Ringan

Ini adalah route yang secara fungsional milik rumah Lawangsewu dan cocok dipindahkan ke CI4 Core.

| Route | Implementasi Saat Ini | Status Migrasi |
|---|---|---|
| `/info-persidangan` | PHP public page | gelombang 2 |
| `/info-persidangan-hijautua` | PHP public page | gelombang 2 |
| `/info-persidangan-stabilo` | PHP public page | gelombang 2 |
| `/monitor-persidangan` | PHP public page | gelombang 2 |
| `/antrian-persidangan` | PHP public page | gelombang 2 |
| `/dashboard-perkara` | PHP public page | gelombang 2 |
| `/dashboard-ecourt` | PHP public page | gelombang 2 |
| `/dashboard-hakim` | PHP public page | gelombang 2 |
| `/widget-pengumuman` | PHP public page | gelombang 2 |
| `/berita-pengadilan` | HTML public page | gelombang 2 |
| `/pengumuman-peradilan` | PHP public page | gelombang 2 |
| `/pengumuman-peradilan-embed` | PHP public page | gelombang 2 |
| `/panduan-embed-pengumuman` | HTML public page | gelombang 2 |
| `/bridge-server10` | HTML public page | gelombang 2 |
| `/radius-ghaib` | PHP public page | gelombang 2 |
| `/radius-kecamatan` | PHP public page | gelombang 2 |
| `/monitor-wa` | HTML public page | gelombang 2 |

## 4. Route API Ringan yang Harus Dibekukan

Ini bukan berarti semua harus langsung dipindah malam ini. Tetapi kontraknya harus dibekukan sekarang.

| Route | Fungsi Saat Ini | Status |
|---|---|---|
| `/api/pengumuman-rss` | feed pengumuman | keep |
| `/api/pengumuman-rss/{source}` | feed pengumuman by source | keep |
| `/api/pengumuman` | alias feed pengumuman | keep compatibility |
| `/api/pengumuman/{source}` | alias feed by source | keep compatibility |
| `/api/server10` | bridge Server10 | keep |
| `/api/server10/health` | health bridge | keep |
| `/api/server10/capabilities` | capability bridge | keep |
| `/api/wa-v2` | WA V2 bridge | keep |
| `/api/wa-v2/{path}` | endpoint path health/qr/send/restart/disconnect | keep |

## 5. Compatibility Alias yang Harus Tetap Hidup

Alias ini masih dipakai untuk kompatibilitas pengguna lama, bookmark, dan embed.

| Alias Lama | Canonical Target |
|---|---|
| `/pa-semarang-pengumuman` | `/pengumuman-peradilan` |
| `/pa-semarang-pengumuman-embed` | `/pengumuman-peradilan-embed` |
| `/pengumuman-rss-widget` | `/widget-pengumuman` |
| `/pa-semarang-embed-snippet` | `/panduan-embed-pengumuman` |
| `/antrian-sidang` | `/antrian-persidangan` |
| `/monitor-antrian-sidang` | `/monitor-persidangan` |
| `/statistik-perkara` | `/dashboard-perkara` |
| `/statistik-ecourt` | `/dashboard-ecourt` |
| `/statistik-hakim` | `/dashboard-hakim` |
| `/biaya-proses-berperkara` | `/biaya-perkara` |
| `/biaya-radius-ghaib` | `/radius-ghaib` |
| `/tabel-radius-kecamatan` | `/radius-kecamatan` |
| `/server10-data` | `/bridge-server10` |
| `/wa-v2/qr` | `/monitor-wa` |

## 6. Route Internal yang Jangan Dijadikan Publik Baru

Route berikut bukan kandidat publik baru dan tidak boleh dibuka sembarangan saat migrasi.

| Route | Alasan |
|---|---|
| `/antrian_controller` | system utility |
| `/generate_slide` | system utility |
| akses langsung `/widgets/views/*` | internal implementation |
| folder `scripts`, `projects`, `logs`, `gateway/data` | sensitif/terblokir |

## 7. Kontrak WA Caraka Sibling App

Ini bukan route portal inti, tetapi kontrak integrasinya harus dijaga.

| Route | Catatan |
|---|---|
| `/wa-caraka-admin/index.php/sso-login` | jalur SSO wrapper yang harus dianggap kontrak integrasi |
| `/wa-caraka-admin/wa/docs/swagger` | dokumentasi admin yang dibuka dari portal |

## 8. Keputusan Migrasi Berdasarkan Route

### Gelombang 1

- `/`
- `/portal`
- seluruh route gateway login/logout/launcher
- `/walkthrough`
- `/daftar-widget`

### Gelombang 2

- widget publik dan dashboard ringan
- route API ringan yang terkait widget/bridge

### Gelombang 3

- compatibility routes dipindahkan ke CI4 route layer
- legacy alias dipertahankan dengan redirect atau mapping internal

## 9. Rule Final

Saat migrasi nanti, pertanyaan yang harus selalu dijawab:

1. Apakah route ini sudah hidup publik?
2. Apakah ada user/bookmark/iframe yang mungkin bergantung padanya?
3. Jika URL target diubah, apakah alias kompatibilitas sudah disiapkan?
4. Apakah route ini portal-facing atau hanya utility internal?

Jika belum ada jawaban untuk empat pertanyaan itu, route jangan dipindah sembarangan.