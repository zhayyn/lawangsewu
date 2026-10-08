# TV Media — Dokumentasi Teknis
> **Versi**: Sinematik v14 | **Dikembangkan oleh**: zhayyn™
> **Update terakhir**: 05 Juni 2026

## 1. Gambaran Umum

TV Media adalah modul Digital Signage berbasis web layar Smart TV Pengadilan Agama Semarang.

| URL | Keterangan |
|---|---|
| `/tvmedia` | Layar TV publik (tanpa autentikasi) |
| `/tvmedia/prakom` | Panel admin kelola konten (superadmin only) |
| `/tvmedia/config` | Endpoint JSON default playlist |

## 2. Database

### Database Utama Lawangsewu
| Item | Nilai |
|---|---|
| **Nama Database** | `lawangsewu_core` |
| **Host** | `localhost` (socket: `/run/mysqld/mysqld.sock`) |
| **Port** | `3306` |
| **Driver** | MySQL / MariaDB 10.11 |
| **User** | `dbprakom` |

### Database SIPP (Data Persidangan)
| Item | Nilai |
|---|---|
| **Nama Database** | `sipp` |
| **Host** | `192.168.88.10` |
| **Port** | `3306` |
| **User** | `admin` |

## 3. Struktur File & Storage

```
/var/www/lawangsewu/
├── app/Http/Controllers/TvMediaController.php
├── routes/web.php
├── storage/app/public/tvmedia/    # ★ FOLDER UPLOAD GAMBAR/VIDEO
├── public/storage/                # Symlink ke storage/app/public/
├── widgets/views/php/public/tvmedia.php
└── docs/tvmedia.md                # Dokumen ini
```

URL publik file upload:
https://lawangsewu.pa-semarang.go.id/storage/tvmedia/{nama-file}

## 4. API Endpoints

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| GET | /tvmedia | Public | Layar TV |
| GET | /tvmedia/config | Public | JSON playlist |
| GET | /tvmedia/prakom | Superadmin | Admin panel |
| POST | /tvmedia/prakom/upload | Superadmin | Upload gambar/video |
| GET | /tvmedia/prakom/uploads | Superadmin | Daftar file upload |
| DELETE | /tvmedia/prakom/uploads/{filename} | Superadmin | Hapus file |

## 5. Batasan Upload

| Tipe | Format | Ukuran Maks |
|---|---|---|
| Gambar | JPG, PNG, GIF, WebP | 15 MB |
| Video | MP4, WebM | 80 MB |

## 6. Docker

### Backup & Pindah Server
```bash
# Export DB
mysqldump -u dbprakom -p lawangsewu_core > backup.sql

# Export media upload
tar czf tvmedia-storage.tar.gz /var/www/lawangsewu/storage/app/public/tvmedia/

# Di server baru
docker compose up -d
mysql -u dbprakom -p lawangsewu_core < backup.sql
tar xzf tvmedia-storage.tar.gz -C /
```

### TV Media Standalone
```bash
docker compose -f docker/tvmedia/docker-compose.standalone.yml up -d
```

## 7. Keyboard Shortcuts

| Tombol | Fungsi |
|---|---|
| → / Space | Slide berikutnya |
| ← | Slide sebelumnya |
| ↓ / ↑ | Pindah kategori |
| F | Toggle fullscreen |
| T | Toggle dark/light mode |
| A | Buka panel admin |
| Esc | Tutup panel admin |
