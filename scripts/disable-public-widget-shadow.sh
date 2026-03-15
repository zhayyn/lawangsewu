#!/usr/bin/env bash
set -euo pipefail

FLAG_FILE="/var/www/html/lawangsewu/runtime-flags/public-widget-shadow-on.flag"
rm -f "$FLAG_FILE"
printf 'public-widget shadow disabled\n'