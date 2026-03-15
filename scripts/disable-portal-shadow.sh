#!/usr/bin/env bash
set -euo pipefail

FLAG_FILE="/var/www/html/lawangsewu/runtime-flags/portal-shadow-on.flag"
rm -f "$FLAG_FILE"
printf 'portal shadow disabled\n'