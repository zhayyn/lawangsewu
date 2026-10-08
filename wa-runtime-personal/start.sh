#!/usr/bin/env bash
# ─── WA Caraka Personal Runtime — Start Script ──────────────────────────────
# Jalankan runtime ini di WSL:
#   chmod +x start.sh
#   ./start.sh
#
# developed by dbprakom™

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PID_FILE="$SCRIPT_DIR/server.pid"
LOG_FILE="$SCRIPT_DIR/server.log"

cd "$SCRIPT_DIR"

# Cek .env
if [[ ! -f ".env" ]]; then
    if [[ -f ".env.example" ]]; then
        cp .env.example .env
        echo "⚠️  File .env dibuat dari .env.example — silakan edit konfigurasi dulu!"
        echo "    nano .env"
        exit 1
    else
        echo "❌ File .env tidak ditemukan!" >&2
        exit 1
    fi
fi

# Cek node_modules
if [[ ! -d "node_modules" ]]; then
    echo "📦 Menginstall dependencies..."
    npm install
fi

# Cek apakah sudah jalan
if [[ -f "$PID_FILE" ]]; then
    PID=$(cat "$PID_FILE")
    if kill -0 "$PID" 2>/dev/null; then
        echo "✅ WA Caraka Personal Runtime sudah berjalan (PID=$PID)"
        exit 0
    fi
fi

# Jalankan
echo "🚀 Menjalankan WA Caraka Personal Runtime..."
nohup node server.mjs >> "$LOG_FILE" 2>&1 &
PID=$!
echo "$PID" > "$PID_FILE"
echo "✅ Runtime dijalankan (PID=$PID)"
echo "📋 Log: tail -f $LOG_FILE"
