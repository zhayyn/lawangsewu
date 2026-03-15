#!/usr/bin/env bash
set -euo pipefail

FLAG_FILE="/var/www/html/lawangsewu/runtime-flags/walkthrough-shadow-on.flag"
rm -f "$FLAG_FILE"
printf 'walkthrough shadow disabled\n'