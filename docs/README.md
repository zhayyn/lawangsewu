# 📚 Dokumentasi Lawangsewu V2

> Portal Digital Terpadu Pengadilan Agama Semarang  
> https://lawangsewu.pa-semarang.go.id

---

## Daftar Dokumen

| No | File | Deskripsi |
|---|---|---|
| 01 | [01_OVERVIEW_SISTEM.md](./01_OVERVIEW_SISTEM.md) | Overview sistem, tech stack, infrastruktur, statistik DB |
| 02 | [02_AUDIT_MODUL_DAN_FITUR.md](./02_AUDIT_MODUL_DAN_FITUR.md) | Audit lengkap semua modul & status fitur (2026-05-08) |
| 03 | [03_PRD_PRODUCT_REQUIREMENTS.md](./03_PRD_PRODUCT_REQUIREMENTS.md) | PRD — goals, user persona, feature set, roadmap |
| 04 | [04_BLUEPRINT_ARSITEKTUR.md](./04_BLUEPRINT_ARSITEKTUR.md) | Blueprint teknis — diagram, DB schema, deployment |
| 05 | [05_WACARAKA_TEKNIS.md](./05_WACARAKA_TEKNIS.md) | Dokumentasi teknis lengkap modul WaCaraka |
| 06 | [06_PANDUAN_SERVER33_WA_BRIDGE.md](./06_PANDUAN_SERVER33_WA_BRIDGE.md) | Panduan setup WA Bridge di Server 33 |
| 07 | [07_LAPORAN_AUDIT_SSO.md](./07_LAPORAN_AUDIT_SSO.md) | Laporan audit keamanan Google SSO |
| 08 | [08_INSIDEN_SSO_INCOGNITO_2026-05-03.md](./08_INSIDEN_SSO_INCOGNITO_2026-05-03.md) | Post-mortem insiden login incognito 2026-05-03 |

---

## Quick Reference

### Proses yang Harus Running

```bash
# Cek semua sekaligus:
ps aux | grep -E "queue:work|reverb:start|php-fpm" | grep -v grep
```

| Proses | Command |
|---|---|
| PHP-FPM | `systemctl status php8.3-fpm` |
| Queue Worker | `php artisan queue:work database --queue=default` |
| Reverb WebSocket | `php artisan reverb:start --host=0.0.0.0 --port=8080` |
| WA Bridge | Di Server 33: `node server.js` atau `pm2 start` |

### Setelah Deploy / Ubah Kode

```bash
npm run build                  # Rebuild frontend assets
php artisan config:cache       # Cache config
php artisan route:cache        # Cache routes
php artisan view:cache         # Cache views
# Jika ada migrasi baru:
php artisan migrate
```

### Cek Kesehatan Sistem

```bash
# WA Bridge
curl http://192.168.88.33:8790/health

# Laravel
curl https://lawangsewu.pa-semarang.go.id/health

# Queue
php artisan queue:monitor

# Failed jobs
php artisan queue:failed
```

---

## Konvensi Penamaan Dokumen

```
NN_JUDUL_DOKUMEN.md
│
├── NN      = Nomor urut 2 digit (01, 02, ...)
└── JUDUL   = Judul uppercase dengan underscore
```

Dokumen audit insiden diberi tanggal: `NN_JUDUL_YYYY-MM-DD.md`

---

## Aturan Update Dokumentasi

Berdasarkan `.agents/agents.md` (Universal Constraints):

> **Docs Sync** — Perubahan signifikan harus didokumentasikan di `docs/`

1. Setiap fitur baru → update `02_AUDIT_MODUL_DAN_FITUR.md`
2. Perubahan arsitektur → update `04_BLUEPRINT_ARSITEKTUR.md`
3. Perubahan WaCaraka → update `05_WACARAKA_TEKNIS.md`
4. Insiden produksi → buat dokumen baru `08+_INSIDEN_...md`
5. Backlog baru → update `03_PRD_PRODUCT_REQUIREMENTS.md` section Roadmap
