#!/usr/bin/env bash
set -euo pipefail

FLAG_FILE="/var/www/html/lawangsewu/runtime-flags/public-widget-shadow-on.flag"
mkdir -p "$(dirname "$FLAG_FILE")"
printf 'enabled=1\n' > "$FLAG_FILE"
printf 'public-widget shadow enabled\n'