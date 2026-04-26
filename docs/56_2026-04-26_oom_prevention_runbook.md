# OOM Prevention Runbook

## Prinsip

- Satu proses, satu pengelola.
- `queue:work` dan `reverb:start` dikelola oleh `systemd` atau `Supervisor`, jangan dua-duanya.
- `schedule:run` hanya dari `cron`.
- Semua task scheduler berat memakai `withoutOverlapping()` dan `runInBackground()`.

## Struktur Aman

- `cron`:
  - `php artisan schedule:run`
- `systemd` atau `Supervisor`:
  - `php artisan queue:work`
  - `php artisan reverb:start`

## Audit Sebelum Ubah Apa Pun

```bash
cd /var/www/lawangsewu
bash ops/scripts/audit_lawangsewu_processes.sh
```

## Jika Tetap Memakai systemd

Pasang unit yang ada di:

- `ops/systemd/lawangsewu-queue.service`
- `ops/systemd/lawangsewu-reverb.service`

Lalu:

```bash
sudo cp ops/systemd/lawangsewu-queue.service /etc/systemd/system/
sudo cp ops/systemd/lawangsewu-reverb.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable lawangsewu-queue.service lawangsewu-reverb.service
sudo systemctl restart lawangsewu-queue.service
sudo systemctl restart lawangsewu-reverb.service
sudo systemctl status lawangsewu-queue.service lawangsewu-reverb.service
```

## Jika Mau Memakai Supervisor

Pastikan `systemd` queue/reverb dimatikan dulu:

```bash
sudo systemctl stop lawangsewu-queue.service lawangsewu-reverb.service
sudo systemctl disable lawangsewu-queue.service lawangsewu-reverb.service
```

Baru pasang:

- `ops/supervisor/lawangsewu-queue.conf`
- `ops/supervisor/lawangsewu-reverb.conf`

## Pasang Cron Scheduler

```bash
bash ops/scripts/install_scheduler_cron.sh
crontab -l
```

## Sesudah Deploy

```bash
cd /var/www/lawangsewu
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan queue:restart
sudo systemctl restart lawangsewu-queue.service
sudo systemctl restart lawangsewu-reverb.service
```

## Verifikasi

```bash
systemctl status lawangsewu-queue.service
systemctl status lawangsewu-reverb.service
ps -ef | grep "artisan queue:work" | grep -v grep
ps -ef | grep "artisan reverb:start" | grep -v grep
crontab -l | grep "schedule:run"
free -h
```

## Tanda Bahaya

- lebih dari satu `queue:work`
- lebih dari satu `reverb:start`
- lebih dari satu baris `schedule:run`
- `queue:listen` masih dipakai di production
- `systemd` dan `Supervisor` aktif bersamaan untuk proses yang sama
