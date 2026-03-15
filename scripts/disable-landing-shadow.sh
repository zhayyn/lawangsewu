#!/usr/bin/env bash
set -euo pipefail

FLAG_FILE="/var/www/html/lawangsewu/runtime-flags/landing-shadow-on.flag"
rm -f "$FLAG_FILE"
printf 'landing shadow disabled\n'