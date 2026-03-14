#!/usr/bin/env bash
set -euo pipefail

FLAG_FILE="/var/www/html/lawangsewu/runtime-flags/widget-directory-shadow-on.flag"
rm -f "$FLAG_FILE"
printf 'widget-directory shadow disabled\n'