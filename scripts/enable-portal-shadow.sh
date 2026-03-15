#!/usr/bin/env bash
set -euo pipefail

FLAG_FILE="/var/www/html/lawangsewu/runtime-flags/portal-shadow-on.flag"
mkdir -p "$(dirname "$FLAG_FILE")"
printf 'enabled=1\n' > "$FLAG_FILE"
printf 'portal shadow enabled\n'