#!/usr/bin/env bash
set -euo pipefail

echo "== Laravel process audit =="
ps -ef | grep -E "php artisan (queue:work|queue:listen|schedule:run|reverb:start)" | grep -v grep || true

echo
echo "== systemd units =="
systemctl list-units --type=service --all | grep -E "lawangsewu-(queue|reverb|gateway)|wa-lawangsewu|wa-bridge" || true

echo
echo "== cron schedule:run =="
crontab -l 2>/dev/null | grep "schedule:run" || true

echo
echo "== root cron schedule:run =="
if command -v sudo >/dev/null 2>&1; then
    sudo crontab -l 2>/dev/null | grep "schedule:run" || true
fi

echo
echo "== system cron schedule:run =="
grep -R "schedule:run" /etc/cron* /var/spool/cron -n 2>/dev/null || true

echo
echo "== memory =="
free -h || true

echo
echo "== top php processes =="
ps -eo pid,ppid,%mem,%cpu,cmd --sort=-%mem | head -n 20
