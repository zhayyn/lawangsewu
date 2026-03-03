# Backup & Recovery

Panduan ini untuk backup harian terenkripsi dan proses restore saat darurat.

## 1) Konfigurasi awal

1. Salin template konfigurasi:

```bash
cp /var/www/html/lawangsewu/scripts/backup.conf.example /var/www/html/lawangsewu/scripts/backup.conf
```

2. Edit file konfigurasi:

```bash
nano /var/www/html/lawangsewu/scripts/backup.conf
```

3. Isi minimal:

- `BACKUP_PASSPHRASE` (wajib)

4. (Opsional) Isi parameter database (`DB_NAME`, `DB_USER`, dst) jika ingin dump database otomatis.

## 2) Test backup manual

```bash
/var/www/html/lawangsewu/scripts/backup-daily.sh
```

Output terenkripsi akan tersimpan di:

- `/var/backups/lawangsewu/lawangsewu_YYYY-MM-DD_HHMMSS.tar.enc`

## 3) Aktifkan jadwal harian (cron)

```bash
/var/www/html/lawangsewu/scripts/setup-backup-cron.sh
```

Default schedule: `02:30` setiap hari.

## 4) Restore dari backup terenkripsi

1. Decrypt:

```bash
export BACKUP_PASSPHRASE='isi-passphrase-kamu'
openssl enc -d -aes-256-cbc -pbkdf2 -iter 100000 \
  -in /var/backups/lawangsewu/lawangsewu_YYYY-MM-DD_HHMMSS.tar.enc \
  -out /tmp/lawangsewu_restore.tar \
  -pass env:BACKUP_PASSPHRASE
```

2. Extract:

```bash
mkdir -p /tmp/lawangsewu_restore
tar -xf /tmp/lawangsewu_restore.tar -C /tmp/lawangsewu_restore
```

3. Restore source code:

```bash
tar -xzf /tmp/lawangsewu_restore/source_*.tar.gz -C /var/www/html/lawangsewu
```

4. Restore git history (opsional):

```bash
git clone /tmp/lawangsewu_restore/repo_*.bundle /var/www/html/lawangsewu_git_restore
```

5. Restore database (jika ada file `db_*.sql`):

```bash
mysql -u USER -p NAMA_DB < /tmp/lawangsewu_restore/db_*.sql
```
