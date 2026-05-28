#!/usr/bin/env bash
# =============================================================================
# check_server33_readiness.sh
# Script verifikasi kesiapan wa-bridge di Server 33 setelah perbaikan media.
#
# Cara pakai:
#   bash scripts/check_server33_readiness.sh
#
# Dijalankan dari root project Laravel (/var/www/lawangsewu)
# =============================================================================

# Jangan pakai set -e agar script terus berjalan meski ada test yang gagal
set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="${SCRIPT_DIR}/.."
ENV_FILE="${PROJECT_DIR}/.env"

# ── Baca .env ─────────────────────────────────────────────────────────────────
read_env() {
    grep -E "^${1}=" "$ENV_FILE" 2>/dev/null | head -1 | cut -d'=' -f2- | tr -d '"' || true
}

SERVER_URL=$(read_env "LW_WA_V2_BASE")
WA_TOKEN=$(read_env "LW_WA_V2_TOKEN")
APP_URL=$(read_env "APP_URL")
SERVER_URL="${SERVER_URL:-http://192.168.88.33:8790}"
WA_TOKEN="${WA_TOKEN:-lawangsewu2026}"

# ── Warna ────────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

PASS=0; FAIL=0; WARN=0

p()  { echo -e "  ${GREEN}✅ PASS${NC} — $*"; ((PASS++)) || true; }
f()  { echo -e "  ${RED}❌ FAIL${NC} — $*"; ((FAIL++)) || true; }
w()  { echo -e "  ${YELLOW}⚠️  WARN${NC} — $*"; ((WARN++)) || true; }
i()  { echo -e "  ${CYAN}ℹ️  INFO${NC} — $*"; }
sec(){ echo -e "\n${BOLD}${YELLOW}▶ $*${NC}"; }

# Helper: jq fallback → gunakan grep/sed jika jq tidak ada
json_get() {
    local json="$1" key="$2"
    if command -v jq &>/dev/null; then
        echo "$json" | jq -r ".${key} // \"?\""
    else
        echo "$json" | grep -o "\"${key}\":[^,}]*" | head -1 | sed 's/.*://;s/[" ]//g'
    fi
}

# Helper HTTP GET — returns body + extra footer: __CODE__xxx__SIZE__yyy
http_get() {
    curl -s --max-time 8 \
        -H "X-WA-V2-Token: ${WA_TOKEN}" \
        -w "\n__CODE__%{http_code}__SIZE__%{size_download}" \
        "$1" 2>/dev/null || echo -e "\n__CODE__000__SIZE__0"
}
get_code() { echo "$1" | grep -o '__CODE__[0-9]*' | grep -o '[0-9]*' || echo "0"; }
get_size() { echo "$1" | grep -o '__SIZE__[0-9]*' | grep -o '[0-9]*' || echo "0"; }
get_body() { echo "$1" | sed 's/__CODE__.*//'; }

# ─── Header ──────────────────────────────────────────────────────────────────
echo ""
echo -e "${BOLD}${BLUE}══════════════════════════════════════════════════════${NC}"
echo -e "${BOLD}${BLUE}  WaCaraka Server 33 — Media Readiness Check${NC}"
echo -e "${BOLD}${BLUE}  Target : ${SERVER_URL}${NC}"
echo -e "${BOLD}${BLUE}  Waktu  : $(date '+%Y-%m-%d %H:%M:%S') WIB${NC}"
echo -e "${BOLD}${BLUE}══════════════════════════════════════════════════════${NC}"

# =============================================================================
sec "TEST 1: Konektivitas dasar ke Server 33"
# =============================================================================
RAW=$(http_get "${SERVER_URL}/health")
CODE=$(get_code "$RAW")
BODY=$(get_body "$RAW")

if [[ "$CODE" == "200" ]]; then
    BRIDGE_VER=$(json_get "$BODY" "bridge")
    WA_STATUS=$(json_get  "$BODY" "status")
    RUNTIME_ST=$(json_get "$BODY" "runtimeState")
    p "Server 33 reachable — HTTP $CODE"
    i "Bridge version : $BRIDGE_VER"
    i "WA status      : $WA_STATUS"
    i "Runtime state  : $RUNTIME_ST"
    if [[ "$WA_STATUS" != "connected" ]]; then
        w "WA tidak connected — scan QR atau reconnect dulu sebelum test media"
    fi
else
    f "Server 33 tidak bisa dijangkau — HTTP $CODE (expected 200)"
    echo ""
    echo -e "  ${RED}Tidak bisa melanjutkan. Periksa apakah wa-bridge berjalan di server 33.${NC}"
    exit 1
fi

# =============================================================================
sec "TEST 2: Endpoint GET /internal/media/ tersedia (indikator Solusi B)"
# =============================================================================
RAW_M=$(http_get "${SERVER_URL}/internal/media/__probe_token_xyz__/probe_check.jpg")
CODE_M=$(get_code "$RAW_M")
SIZE_M=$(get_size "$RAW_M")
BODY_M=$(get_body "$RAW_M")

MEDIA_EP_READY=false
case "$CODE_M" in
    404)
        f "Endpoint /internal/media/ TIDAK ADA (HTTP 404)"
        i "Solusi B belum diimplementasikan di wa-bridge"
        ;;
    200)
        if [[ "$SIZE_M" -gt 0 ]]; then
            p "Endpoint /internal/media/ ADA & mengembalikan data (HTTP 200, ${SIZE_M} bytes)"
            MEDIA_EP_READY=true
        else
            w "Endpoint /internal/media/ ada (HTTP 200) tapi body kosong"
        fi
        ;;
    400|422)
        p "Endpoint /internal/media/ ADA — merespons error validasi untuk token dummy (HTTP $CODE_M) — NORMAL"
        MEDIA_EP_READY=true
        ;;
    401|403)
        w "Endpoint /internal/media/ ada tapi menolak auth (HTTP $CODE_M) — cek token WA_TOKEN"
        ;;
    *)
        w "Endpoint /internal/media/ merespons HTTP $CODE_M — perlu dicek manual"
        ;;
esac

# =============================================================================
sec "TEST 3: Pesan media terbaru di DB — apakah mengandung dataUrl (indikator Solusi A)"
# =============================================================================
DB_RESULT=$(cd "$PROJECT_DIR" && php -r "
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();
    \$msg = App\Models\WaCarakaMessage::query()
        ->whereIn('message_type', ['image','video','audio','document','sticker'])
        ->where('direction', 'inbound')
        ->orderByDesc('id')
        ->first(['id','message_type','metadata','created_at']);
    if (!msg) { echo 'NONE'; exit; }
    \$msg = App\Models\WaCarakaMessage::query()
        ->whereIn('message_type', ['image','video','audio','document','sticker'])
        ->where('direction', 'inbound')
        ->orderByDesc('id')
        ->first(['id','message_type','metadata','created_at']);
    if (!\$msg) { echo 'NONE'; exit; }
    \$meta    = is_array(\$msg->metadata) ? \$msg->metadata : [];
    \$media   = \$meta['media'] ?? null;
    \$dataUrl = \$media['dataUrl'] ?? null;
    \$url     = \$media['url'] ?? null;
    \$isBase64   = is_string(\$dataUrl) && str_starts_with(\$dataUrl, 'data:');
    \$isProxy    = is_string(\$url) && str_contains(\$url, '/wa-caraka/media/');
    \$isInternal = is_string(\$url) && str_contains(\$url, '/internal/media/');
    echo implode('|', [
        \$msg->id,
        \$msg->message_type,
        (string)\$msg->created_at,
        \$isBase64   ? 'true' : 'false',
        \$isProxy    ? 'true' : 'false',
        \$isInternal ? 'true' : 'false',
        substr((string)(\$url ?? ''), 0, 80),
    ]);
" 2>/dev/null || echo "NONE")

SOLUSI_A_READY=false
if [[ "$DB_RESULT" == "NONE" || -z "$DB_RESULT" ]]; then
    w "Tidak ada pesan media inbound di DB — kirim gambar dari WA dulu, lalu cek ulang"
    i "Hint: Kirim foto ke nomor WA bot, tunggu beberapa detik, lalu jalankan script ini lagi"
else
    IFS='|' read -r MSG_ID MSG_TYPE MSG_DATE IS_BASE64 IS_PROXY IS_INTERNAL URL_VAL <<< "$DB_RESULT"
    i "Pesan media terbaru: ID=$MSG_ID  type=$MSG_TYPE  tanggal=$MSG_DATE"
    
    if [[ "$IS_BASE64" == "true" ]]; then
        p "dataUrl mengandung base64 — Solusi A AKTIF ✓"
        SOLUSI_A_READY=true
    elif [[ "$IS_PROXY" == "true" ]]; then
        w "dataUrl mengandung proxy Laravel URL — media mungkin berhasil diunduh saat webhook"
        i "URL: $URL_VAL"
        SOLUSI_A_READY=true
    elif [[ "$IS_INTERNAL" == "true" ]]; then
        f "URL masih menunjuk ke /internal/media/ — download saat webhook GAGAL, Solusi A belum aktif"
        i "URL: $URL_VAL"
    else
        w "URL format tidak dikenal: $URL_VAL"
    fi
fi

# =============================================================================
sec "TEST 4: File media tersimpan di storage lokal Laravel"
# =============================================================================
STORAGE_PATH="${PROJECT_DIR}/storage/app/public/wa-media"
FILE_COUNT=0
if [[ -d "$STORAGE_PATH" ]]; then
    FILE_COUNT=$(find "$STORAGE_PATH" -type f 2>/dev/null | wc -l | tr -d ' ')
fi

if [[ "$FILE_COUNT" -gt 0 ]]; then
    DIR_SIZE=$(du -sh "$STORAGE_PATH" 2>/dev/null | cut -f1 || echo "?")
    p "Storage lokal berisi ${FILE_COUNT} file media (total: ${DIR_SIZE})"
    i "Contoh file:"
    find "$STORAGE_PATH" -type f 2>/dev/null | head -3 | while read -r fpath; do
        echo "     → $(basename "$(dirname "$fpath")")/$(basename "$fpath")"
    done
else
    f "Storage lokal wa-media KOSONG — tidak ada media yang berhasil diunduh"
    i "Path: $STORAGE_PATH"
fi

# =============================================================================
sec "TEST 5: Symlink public/storage untuk akses publik"
# =============================================================================
if [[ -L "${PROJECT_DIR}/public/storage" ]]; then
    p "Symlink public/storage ada — php artisan storage:link sudah dijalankan"
else
    f "Symlink public/storage TIDAK ADA"
    i "Jalankan: cd ${PROJECT_DIR} && php artisan storage:link"
fi

# =============================================================================
sec "TEST 6: Live webhook simulation — kirim dummy image webhook ke Laravel"
# =============================================================================
WEBHOOK_URL="${APP_URL:-http://127.0.0.1}/api/wa-caraka/webhook/inbound"
WH_TOKEN=$(read_env "WA_WEBHOOK_TOKEN")
WH_TOKEN="${WH_TOKEN:-$WA_TOKEN}"

# Buat dummy payload dengan dataUrl base64 kecil (1x1 pixel transparan PNG)
TINY_PNG="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=="
DUMMY_FROM="62819999READYCHECK@s.whatsapp.net"
DUMMY_ID="READYCHECK_$(date +%s)"

WH_PAYLOAD=$(cat <<EOF
{
  "from": "${DUMMY_FROM}",
  "type": "image",
  "id": "${DUMMY_ID}",
  "timestamp": $(date +%s),
  "text": "",
  "media": {
    "kind": "image",
    "mimetype": "image/png",
    "fileName": "readiness-check.png",
    "dataUrl": "${TINY_PNG}",
    "url": "${TINY_PNG}"
  }
}
EOF
)

WH_RAW=$(curl -s --max-time 10 \
    -X POST \
    -H "Content-Type: application/json" \
    -H "X-WA-V2-Token: ${WH_TOKEN}" \
    -d "$WH_PAYLOAD" \
    -w "\n__CODE__%{http_code}" \
    "${WEBHOOK_URL}" 2>/dev/null || echo -e "\n__CODE__000")

WH_CODE=$(get_code "$WH_RAW")
WH_BODY=$(get_body "$WH_RAW")

case "$WH_CODE" in
    201)
        p "Webhook Laravel menerima pesan dummy (HTTP 201)"
        i "Response: $WH_BODY"
        ;;
    202)
        w "Webhook diterima tapi di-skip (HTTP 202) — mungkin difilter sebagai health-check"
        ;;
    401|403)
        f "Webhook ditolak auth (HTTP $WH_CODE) — cek WA_WEBHOOK_TOKEN di .env"
        i "Token dipakai: $WH_TOKEN"
        ;;
    000)
        w "Tidak bisa reach webhook endpoint — cek APP_URL di .env (saat ini: ${APP_URL:-tidak diset})"
        ;;
    *)
        w "Webhook merespons HTTP $WH_CODE — Response: $(echo "$WH_BODY" | head -c 200)"
        ;;
esac

# =============================================================================
sec "RINGKASAN HASIL"
# =============================================================================
echo ""
printf "  %b%d PASS%b   %b%d FAIL%b   %b%d WARN%b\n" \
    "$GREEN" "$PASS" "$NC" \
    "$RED"   "$FAIL" "$NC" \
    "$YELLOW" "$WARN" "$NC"
echo ""

if [[ "$FAIL" -eq 0 ]]; then
    echo -e "${GREEN}${BOLD}  ✅ SEMUA TEST LULUS — Server 33 siap melayani media!${NC}"
elif [[ "$FAIL" -le 2 && "$SOLUSI_A_READY" == "true" ]]; then
    echo -e "${YELLOW}${BOLD}  ⚠️  Sebagian test gagal tapi Solusi A aktif — media baru akan berfungsi.${NC}"
else
    echo -e "${RED}${BOLD}  ❌ Server 33 BELUM SIAP — ${FAIL} test gagal.${NC}"
    echo ""
    echo -e "${BOLD}  Langkah selanjutnya:${NC}"
    [[ "${MEDIA_EP_READY:-false}" == "false" && "${SOLUSI_A_READY:-false}" == "false" ]] && \
        echo -e "  1. Implementasi Solusi A atau B di wa-bridge Server 33"
    [[ "$FILE_COUNT" -eq 0 ]] && \
        echo -e "  2. Kirim gambar dari WA lalu cek ulang storage lokal"
    ! [[ -L "${PROJECT_DIR}/public/storage" ]] && \
        echo -e "  3. Jalankan: php artisan storage:link"
fi

echo ""
echo -e "  ${CYAN}Selesai pada $(date '+%H:%M:%S')${NC}"
echo ""
