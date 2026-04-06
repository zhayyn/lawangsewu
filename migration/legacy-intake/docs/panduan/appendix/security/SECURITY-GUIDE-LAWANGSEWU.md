# Security Guide Lawangsewu

Panduan ini merangkum dokumen keamanan agar pembaca tidak perlu membuka banyak runbook sekaligus.

## Jalur Baca Ringkas

Jika butuh gambaran cepat, cukup baca urutan ini:

1. `REMOTE-ACCESS-SECURITY-SERVER9-SIMPLE.md`
2. `SECURITY-HARDENING.md`
3. `CLOUDFLARE-CHECKLIST.md`

Jika butuh detail teknis penuh, baru lanjutkan ke `REMOTE-ACCESS-SECURITY-SERVER9.md`.

## Ringkasan Posture Saat Ini

- remote access utama memakai `WireGuard + SSH`
- VS Code Tunnel cloud dinonaktifkan untuk mode hardening saat ini
- audit rutin, rotasi secret, dan backup recovery tetap wajib dipertahankan
- Cloudflare harus dikonfigurasi hati-hati agar endpoint monitor dan WA admin tidak terkena challenge yang tidak perlu

## Dokumen Keamanan Berdasarkan Tujuan

- remote access dan alasan arsitektur: `REMOTE-ACCESS-SECURITY-SERVER9.md`
- versi ringkas untuk briefing non-teknis: `REMOTE-ACCESS-SECURITY-SERVER9-SIMPLE.md`
- ringkasan satu halaman: `REMOTE-ACCESS-SECURITY-SERVER9-ONEPAGE.md`
- hardening operasional repo dan server: `SECURITY-HARDENING.md`
- rotasi credential: `SECRET-ROTATION.md`
- checklist Cloudflare: `CLOUDFLARE-CHECKLIST.md`
- playbook akses Cloudflare admin: `CLOUDFLARE-ACCESS-ADMIN-PLAYBOOK.md`
- backup dan recovery: `BACKUP-RECOVERY.md`

## Rule Praktis

- buka `SECURITY-HARDENING.md` untuk audit rutin dan prioritas operasional
- buka `CLOUDFLARE-CHECKLIST.md` hanya saat ada isu challenge, 403, atau bypass WAF yang perlu dijaga
- buka `SECRET-ROTATION.md` hanya saat ada rotasi atau indikasi kebocoran credential