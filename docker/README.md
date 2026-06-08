# 🐳 Lawangsewu Docker Deployment Guide

Panduan ini ditujukan sebagai cadangan (*backup deployment*) apabila aplikasi Lawangsewu ingin dipindahkan ke server baru dengan cepat tanpa instalasi *bare-metal* manual. 

Arsitektur Docker ini membungkus Lawangsewu menjadi 5 *containers* microservice:
1. `app` (PHP-FPM 8.3)
2. `web` (Nginx Alpine)
3. `worker` (PHP CLI + Supervisor untuk Queue & Reverb)
4. `db` (MySQL 8.0)
5. `redis` (Redis Alpine)

---

## 🚀 Cara Menjalankan di Server Baru

Jika server tujuan sudah memiliki [Docker](https://docs.docker.com/get-docker/) dan [Docker Compose](https://docs.docker.com/compose/install/) terinstal, ikuti langkah berikut:

### 1. Kloning Repository
```bash
git clone git@github.com:zhayyn/lawangsewu.git
cd lawangsewu
```

### 2. Setup Environment Variables
Salin file `.env.example` menjadi `.env`.
```bash
cp .env.example .env
```
Sesuaikan konfigurasi database dan Redis di `.env` (pastikan DB `host` mengarah ke nama container, bukan `127.0.0.1`):
```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=lawangsewu
DB_USERNAME=lawangsewu
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. Build & Jalankan Container
Jalankan perintah ini untuk mem-build image dan menyalakan semua sistem di background:
```bash
docker-compose up -d --build
```

### 4. Setup Aplikasi Laravel (Di Dalam Container)
Jalankan sekumpulan perintah ini untuk menginstal dependensi dan migrasi database:
```bash
# Install PHP Dependencies
docker-compose exec app composer install --optimize-autoloader --no-dev

# Install NPM & Build Assets (Vite)
docker-compose exec app npm install
docker-compose exec app npm run build

# Generate App Key
docker-compose exec app php artisan key:generate

# Migrasi Database (dan seed jika diperlukan)
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed --class=PermissionSeeder
```

### 5. Setup Kunci Passport OAuth2 (Penting untuk SSO)
Jika sistem baru di-deploy, buat kunci enkripsi OAuth2 baru:
```bash
docker-compose exec app php artisan passport:keys
```
*Catatan: Izin akses 660 otomatis ter-handle jika di-generate dari dalam container.*

---

## 🛠️ Perintah Troubleshooting Berguna

Melihat logs server Nginx (Error web):
```bash
docker-compose logs -f web
```

Melihat logs Queue Worker & WebSocket (Reverb):
```bash
docker-compose logs -f worker
```

Masuk ke dalam terminal container PHP utama:
```bash
docker-compose exec app bash
```

---
*Developed by dbprakom™ - Dipersiapkan oleh @devops Agent*
