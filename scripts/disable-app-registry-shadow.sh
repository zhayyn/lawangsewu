#!/usr/bin/env bash
set -euo pipefail

FLAG_FILE="/var/www/html/lawangsewu/runtime-flags/app-registry-shadow-on.flag"
rm -f "$FLAG_FILE"
printf 'app-registry shadow disabled\n'