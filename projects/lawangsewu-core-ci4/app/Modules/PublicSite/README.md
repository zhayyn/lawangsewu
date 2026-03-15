# PublicSite Module Staging

Modul ini akan menangani wajah depan Lawangsewu.

## Scope

- landing page `/`
- visual branding
- inline login panel pada landing
- halaman publik front-facing yang memang milik rumah utama

## Source Lama

- `/var/www/html/lawangsewu/widgets/views/php/public/landing.php`

## Boundary Penting

- halaman depan tetap ringan
- login panel tetap mengarah ke auth portal yang sama
- visual boleh berevolusi tanpa mengubah kontrak login dan launcher utama

## Target Refactor

1. pisahkan logic login handling dari tampilan visual
2. pertahankan prefix compatibility `/lawangsewu`
3. pertahankan route `/` sebagai entry utama