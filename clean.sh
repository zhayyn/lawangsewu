#!/bin/bash
# ═══════════════════════════════════════════════════════════════
# clean.sh — Stealth & Cleanup AI Tools di Server Produksi
# ═══════════════════════════════════════════════════════════════
# Target: Antigravity, VS Code Remote, GitHub Copilot,
#         Google Gemini Code Assist/CLI, dan artefak terkait
#
# DUA MODE:
#   source clean.sh         → Aktifkan stealth (awal kerja)
#   ./clean.sh              → Bersihkan jejak (akhir kerja)
#   ./clean.sh --force      → Bersihkan tanpa konfirmasi
#
# Catatan: Script ini TIDAK menghapus kode proyek Anda.
# ═══════════════════════════════════════════════════════════════

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# ─── Deteksi: di-source atau di-execute? ──────────────────────
_is_sourced() {
    [[ "${BASH_SOURCE[0]}" != "${0}" ]]
}

# ─── MODE 1: STEALTH ON (saat di-source) ─────────────────────
stealth_on() {
    echo -e "${CYAN}═══════════════════════════════════════${NC}"
    echo -e "${CYAN}  🥷 MODE STEALTH AKTIF${NC}"
    echo -e "${CYAN}═══════════════════════════════════════${NC}"

    unset HISTFILE
    export HISTSIZE=0
    export HISTFILESIZE=0
    set +o history
    export STEALTH_SESSION=1

    echo -e "${GREEN}  ✓ Bash history    : MATI${NC}"
    echo -e "${GREEN}  ✓ Sesi dimulai    : $(date '+%H:%M:%S')${NC}"
    echo ""
    echo -e "${YELLOW}  Selesai kerja? Jalankan:${NC}"
    echo -e "${YELLOW}    ./clean.sh${NC}"
    echo ""
}

if _is_sourced; then
    stealth_on
    return 0 2>/dev/null
fi

# ═══════════════════════════════════════════════════════════════
# MODE 2: PEMBERSIHAN LENGKAP (saat di-execute)
# ═══════════════════════════════════════════════════════════════

set -euo pipefail

FORCE=false
[[ "${1:-}" == "--force" ]] && FORCE=true

confirm() {
    if $FORCE; then return 0; fi
    echo -en "${YELLOW}$1 [y/N]: ${NC}"
    read -r ans
    [[ "$ans" =~ ^[Yy]$ ]]
}

calc_size() {
    if [[ -e "$1" ]]; then
        du -sh "$1" 2>/dev/null | awk '{print $1}'
    else
        echo "0"
    fi
}

safe_rm() {
    local target="$1" label="${2:-$1}"
    if [[ -e "$target" ]]; then
        local size
        size=$(calc_size "$target")
        rm -rf "$target"
        echo -e "  ${GREEN}✓${NC} $label ${CYAN}($size)${NC}"
    fi
}

echo ""
echo -e "${CYAN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${CYAN}  🧹 PEMBERSIHAN JEJAK AI TOOLS — SERVER PRODUKSI${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════${NC}"
echo ""

# ── FASE 0: Git Commit & Push ─────────────────────────────────
echo -e "${YELLOW}[0/8] Mengecek status Git...${NC}"
if git -C /var/www/lawangsewu rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    # Pastikan clean.sh diabaikan secara lokal tanpa mengubah .gitignore
    grep -qx "clean.sh" /var/www/lawangsewu/.git/info/exclude 2>/dev/null || echo "clean.sh" >> /var/www/lawangsewu/.git/info/exclude
    git -C /var/www/lawangsewu rm --cached clean.sh 2>/dev/null || true

    if [[ -n $(git -C /var/www/lawangsewu status --porcelain) ]]; then
        echo -e "${CYAN}  Ada perubahan yang belum di-commit.${NC}"
        if confirm "  Ingin commit dan push SEMUA (termasuk Docs AI) ke GitHub sekarang?"; then
            git -C /var/www/lawangsewu add .
            echo -en "${YELLOW}  Masukkan pesan commit: ${NC}"
            read -r commit_msg
            [[ -z "$commit_msg" ]] && commit_msg="chore: auto-commit sebelum cleanup"
            git -C /var/www/lawangsewu commit -m "$commit_msg" || echo "  Tidak ada yang dicommit."
            echo -e "${CYAN}  Mendorong ke GitHub...${NC}"
            git -C /var/www/lawangsewu push || echo -e "${RED}  Gagal push ke GitHub.${NC}"
            echo -e "  ${GREEN}✓${NC} Git push selesai"
        else
            echo "  Dilewati."
        fi
    else
        echo -e "  ${GREEN}✓${NC} Working tree bersih."
        if confirm "  Push commit lokal (jika ada) ke GitHub sekarang?"; then
            echo -e "${CYAN}  Mendorong ke GitHub...${NC}"
            git -C /var/www/lawangsewu push || echo -e "${RED}  Gagal push ke GitHub.${NC}"
            echo -e "  ${GREEN}✓${NC} Git push selesai"
        else
            echo "  Dilewati."
        fi
    fi
else
    echo "  Bukan repository git. Dilewati."
fi
echo ""
# ── Tampilkan ringkasan ───────────────────────────────────────
echo "  Lokasi                              Ukuran"
echo "  ─────────────────────────────────── ──────────"
for dir in \
    "$HOME/.antigravity-server" \
    "$HOME/.vscode-server" \
    "$HOME/.gemini" \
    "$HOME/.copilot" \
    "$HOME/.cache"; do
    [[ -d "$dir" ]] && printf "  %-38s %s\n" "$dir" "$(calc_size "$dir")"
done
echo ""

if ! confirm "Lanjutkan pembersihan?"; then
    echo "Dibatalkan."
    exit 0
fi
echo ""

# ── FASE 1: Kill proses ──────────────────────────────────────
echo -e "${YELLOW}[1/8] Menghentikan proses AI...${NC}"
pkill -u "$(whoami)" -f "antigravity" 2>/dev/null && echo "  → Antigravity dihentikan" || echo "  → Tidak ada proses Antigravity"
pkill -u "$(whoami)" -f "\.vscode-server" 2>/dev/null && echo "  → VS Code Remote dihentikan" || echo "  → Tidak ada proses VS Code"
sleep 2

# ── FASE 2: Antigravity ──────────────────────────────────────
echo -e "${YELLOW}[2/8] Menghapus Antigravity...${NC}"
safe_rm "$HOME/.antigravity-server" "Antigravity server binary & extensions"

# ── FASE 3: VS Code Remote ───────────────────────────────────
echo -e "${YELLOW}[3/8] Menghapus VS Code Remote Server...${NC}"
safe_rm "$HOME/.vscode-server" "VS Code Remote (Copilot, Gemini Code Assist, dll)"

# ── FASE 4: Gemini CLI ───────────────────────────────────────
echo -e "${YELLOW}[4/8] Menghapus Gemini CLI data...${NC}"
safe_rm "$HOME/.gemini" "Gemini CLI (OAuth, conversations, brain, knowledge)"

# ── FASE 5: Copilot & cache ──────────────────────────────────
echo -e "${YELLOW}[5/8] Menghapus Copilot & cache terkait...${NC}"
safe_rm "$HOME/.copilot" "GitHub Copilot config"
safe_rm "$HOME/.cache/cloud-code" "Google Cloud Code cache"
safe_rm "$HOME/.cache/google-vscode-extension" "Google extension cache"
safe_rm "$HOME/.cache/vscode-ripgrep" "VS Code ripgrep cache"
safe_rm "$HOME/.cache/Microsoft" "Microsoft cache"
safe_rm "$HOME/.npm/_logs" "npm debug logs"
safe_rm "$HOME/.node_repl_history" "Node REPL history"

# Puppeteer (besar, tanya dulu)
if [[ -d "$HOME/.cache/puppeteer" ]]; then
    if confirm "  Hapus Puppeteer cache ($(calc_size "$HOME/.cache/puppeteer"))?"; then
        safe_rm "$HOME/.cache/puppeteer" "Puppeteer browser cache"
    fi
fi

# Playwright
if [[ -d "$HOME/.cache/ms-playwright-go" ]]; then
    if confirm "  Hapus Playwright cache ($(calc_size "$HOME/.cache/ms-playwright-go"))?"; then
        safe_rm "$HOME/.cache/ms-playwright-go" "Playwright browser cache"
    fi
fi

# ── FASE 6: Jejak di proyek ──────────────────────────────────
echo -e "${YELLOW}[6/8] Membersihkan jejak di proyek...${NC}"
if [[ -e "/var/www/.vscode" ]]; then
    size=$(calc_size "/var/www/.vscode")
    sudo rm -rf /var/www/.vscode
    echo -e "  ${GREEN}✓${NC} VS Code settings di /var/www ${CYAN}($size)${NC}"
fi


# AI-generated docs (opsional)
AI_DOCS=(
    "ARCHITECTURE_OVERVIEW.md"
    "CODE_CHANGES_REFERENCE.md"
    "FRONTEND_MEDIA_HANDLING_ANALYSIS.md"
    "IMPLEMENTATION_GUIDE_2026_04.md"
    "INBOX_OPTIMIZATION_AUDIT.md"
    "INBOX_OPTIMIZATION_PLAN.md"
    "OPTIMIZATION_SUMMARY.md"
    "QUICK_REFERENCE.md"
    "SPRINT_3_COMPLETION_REPORT.md"
    "SPRINT_3_SIPP_HUB_INTEGRATION.md"
    "TECHNICAL_ASSESSMENT_2026_04.md"
)
has_docs=false
for doc in "${AI_DOCS[@]}"; do
    [[ -f "/var/www/lawangsewu/$doc" ]] && has_docs=true && break
done
if $has_docs; then
    if confirm "  Hapus file dokumentasi AI-generated di root proyek?"; then
        for doc in "${AI_DOCS[@]}"; do
            safe_rm "/var/www/lawangsewu/$doc" "$doc"
        done
    fi
fi

# docs/ folder
if ls /var/www/lawangsewu/docs/*.md &>/dev/null; then
    if confirm "  Hapus semua .md di /var/www/lawangsewu/docs/?"; then
        rm -f /var/www/lawangsewu/docs/*.md
        echo -e "  ${GREEN}✓${NC} docs/*.md dihapus"
    fi
fi

# ── FASE 7: Git config ───────────────────────────────────────
echo -e "${YELLOW}[7/8] Membersihkan git config...${NC}"
git -C /var/www/lawangsewu config --unset extensions.worktreeConfig 2>/dev/null || true
git -C /var/www/lawangsewu config --unset core.worktreeConfig 2>/dev/null || true
echo -e "  ${GREEN}✓${NC} Git config dibersihkan"

# ── FASE 8: Bash history ─────────────────────────────────────
echo -e "${YELLOW}[8/8] Membersihkan bash history...${NC}"
cat /dev/null > "$HOME/.bash_history" 2>/dev/null
history -c 2>/dev/null || true
echo -e "  ${GREEN}✓${NC} Bash history dibersihkan"



# ── Selesai ───────────────────────────────────────────────────
echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ PEMBERSIHAN SELESAI${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "  ${CYAN}→ Logout dan login ulang untuk reset environment${NC}"
echo -e "  ${CYAN}→ Verifikasi: ls -la ~/.*${NC}"
echo ""

# Note: Script clean.sh ini dipertahankan secara permanen dan diabaikan dari Git.
