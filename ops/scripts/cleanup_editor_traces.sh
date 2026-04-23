#!/usr/bin/env bash
set -euo pipefail

USER_HOME="/home/dbprakom"

pkill -u dbprakom -f "/home/dbprakom/.cache/cloud-code" || true
pkill -u dbprakom -f "cloudcode_cli" || true
pkill -u dbprakom -f "googlecloudtools.cloudcode" || true
pkill -u dbprakom -f "google.geminicodeassist" || true
pkill -u dbprakom -f "openai.chatgpt" || true

rm -rf "${USER_HOME}/.cache/cloud-code"
rm -rf "${USER_HOME}/.cache/Code"
rm -rf "${USER_HOME}/.config/Code"
rm -rf "${USER_HOME}/.codex"
rm -rf "${USER_HOME}/.vscode-server/extensions/googlecloudtools.cloudcode-"*
rm -rf "${USER_HOME}/.vscode-server/extensions/google.geminicodeassist-"*
rm -rf "${USER_HOME}/.vscode-server/extensions/openai.chatgpt-"*

if [[ "${1:-}" == "--full-after-disconnect" ]]; then
  pkill -u dbprakom -f "/home/dbprakom/.vscode-server" || true
  rm -rf "${USER_HOME}/.vscode-server"
fi

echo "Editor traces cleanup selesai."
