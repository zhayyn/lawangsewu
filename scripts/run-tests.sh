#!/bin/bash
# =============================================================================
# Lawangsewu Test Runner — Production Readiness Suite
# Run each test class sequentially to avoid RefreshDatabase race conditions.
# Usage: bash scripts/run-tests.sh [--quick] [--full]
#
# developed by dbprakom™
# =============================================================================

set -euo pipefail

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
RESET='\033[0m'

PASS_COUNT=0
FAIL_COUNT=0
SKIP_COUNT=0
TOTAL_TESTS=0
START_TIME=$(date +%s)

log_header() {
    echo -e "\n${BLUE}${BOLD}═══════════════════════════════════════════════════════════${RESET}"
    echo -e "${BLUE}${BOLD}  $1${RESET}"
    echo -e "${BLUE}${BOLD}═══════════════════════════════════════════════════════════${RESET}\n"
}

run_test() {
    local label="$1"
    local path="$2"
    local optional="${3:-false}"

    printf "  %-55s" "$label"

    if [ ! -f "$path" ] && [ ! -d "$path" ]; then
        echo -e "${YELLOW}[SKIP - not found]${RESET}"
        ((SKIP_COUNT++))
        return 0
    fi

    # Run test, capture output
    local output
    if output=$(php artisan test "$path" 2>&1); then
        local tests=$(echo "$output" | grep -oE '[0-9]+ passed' | grep -oE '[0-9]+' | head -1 || echo "0")
        echo -e "${GREEN}[PASS] ${tests} tests${RESET}"
        ((PASS_COUNT++))
        ((TOTAL_TESTS += tests))
    else
        local failed=$(echo "$output" | grep -oE '[0-9]+ failed' | grep -oE '[0-9]+' | head -1 || echo "?")
        echo -e "${RED}[FAIL] ${failed} tests failed${RESET}"
        ((FAIL_COUNT++))

        if [ "$optional" != "true" ]; then
            echo -e "${RED}  └─ Error:${RESET}"
            echo "$output" | grep -E "FAILED|Expected|Error" | head -5 | sed 's/^/     /'
        fi
    fi
}

# =============================================================================
# SUITE 1: AUTHENTICATION & SECURITY
# =============================================================================
log_header "1. Authentication & Security"
run_test "Auth: Login / Logout Flow"         "tests/Feature/Auth"
run_test "Security: WaCaraka Webhook"        "tests/Feature/Modules/WaCarakaWebhookTest.php"
run_test "System: Health Endpoints"          "tests/Feature/SystemHealthTest.php"

# =============================================================================
# SUITE 2: PORTAL & CORE MODULES
# =============================================================================
log_header "2. Portal & Core Modules"
run_test "Dashboard Flow"                    "tests/Feature/Portal/DashboardFlowTest.php"
run_test "Portal API Contract"               "tests/Feature/Portal/PortalApiContractTest.php"
run_test "Widget Compat Smoke"               "tests/Feature/Portal/WidgetCompatSmokeTest.php"
run_test "CCTV API"                          "tests/Feature/Portal/CctvApiTest.php"
run_test "Chat Flow"                         "tests/Feature/Portal/ChatFlowTest.php"
run_test "Pilar Module"                      "tests/Feature/Portal/PilarModuleTest.php"

# =============================================================================
# SUITE 3: OPERATIONAL MODULES
# =============================================================================
log_header "3. Operational Modules"
run_test "TDMS (Tech Device Mgmt)"           "tests/Feature/Tdms/TdmsReadinessTest.php"
run_test "SIPP Hub (Cache Proxy)"            "tests/Feature/Modules/SippHubTest.php"
run_test "Pelayanan PTSP"                    "tests/Feature/Portal/PelayananPtspTest.php"
run_test "PTSP Queue Flow"                   "tests/Feature/Portal/PtspQueueFlowTest.php"
run_test "Antrian Sidang"                    "tests/Feature/Portal/SidangQueueFlowTest.php"
run_test "PAK PP (AI Generator)"             "tests/Feature/Portal/PakPpTest.php"
run_test "Laporan Controller"                "tests/Feature/LaporanControllerTest.php"

# =============================================================================
# SUITE 4: OMNICHANNEL & WA CARAKA
# =============================================================================
log_header "4. Omnichannel & WA Caraka"
run_test "WaCaraka Module"                   "tests/Feature/Modules/WaCarakaModuleTest.php"
run_test "WaCaraka Attachments"              "tests/Feature/Modules/WaCarakaAttachmentFlowTest.php"
run_test "Omnichannel LiveChat"              "tests/Feature/Modules/OmnichannelLiveChatTest.php"

# =============================================================================
# SUITE 5: BUKU TAMU & SATELLITE
# =============================================================================
log_header "5. Buku Tamu & Satellite"
run_test "Guestbook (Buku Tamu)"             "tests/Feature/Portal/GuestbookFlowTest.php"
run_test "Pendopo Satellite"                 "tests/Feature/Portal/PendopoSatelliteTest.php"

# =============================================================================
# SUITE 6: ADMIN PANEL
# =============================================================================
log_header "6. Admin Panel"
run_test "Admin: User Access Approval"       "tests/Feature/Admin/UserAccessApprovalTest.php"
run_test "Admin: CCTV Management"           "tests/Feature/Admin/CctvManagementTest.php"
run_test "Admin: Pendopo Management"         "tests/Feature/Admin/PendopoManagementTest.php"
run_test "Admin: Feature Permissions"        "tests/Feature/Admin/FeaturePermissionUiAndAuditTest.php"
run_test "Admin: System Monitor"             "tests/Feature/Admin/SystemMonitorTest.php"

# =============================================================================
# SUITE 7: VALIDATION & EDGE CASES
# =============================================================================
log_header "7. Validation & Edge Cases"
run_test "Validation Suite"                  "tests/Feature/Validation"
run_test "Error Handling"                    "tests/Feature/ErrorHandling"
run_test "Edge Cases"                        "tests/Feature/EdgeCases"

# =============================================================================
# SUMMARY
# =============================================================================
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
MINUTES=$((DURATION / 60))
SECONDS=$((DURATION % 60))

echo ""
echo -e "${BLUE}${BOLD}═══════════════════════════════════════════════════════════${RESET}"
echo -e "${BOLD}  PRODUCTION READINESS REPORT${RESET}"
echo -e "${BLUE}${BOLD}═══════════════════════════════════════════════════════════${RESET}"
echo ""
echo -e "  ${BOLD}Suites Passed :${RESET} ${GREEN}${PASS_COUNT}${RESET}"
echo -e "  ${BOLD}Suites Failed :${RESET} ${RED}${FAIL_COUNT}${RESET}"
echo -e "  ${BOLD}Suites Skipped:${RESET} ${YELLOW}${SKIP_COUNT}${RESET}"
echo -e "  ${BOLD}Total Tests   :${RESET} ${TOTAL_TESTS}+"
echo -e "  ${BOLD}Duration      :${RESET} ${MINUTES}m ${SECONDS}s"
echo ""

if [ "$FAIL_COUNT" -eq 0 ]; then
    echo -e "  ${GREEN}${BOLD}✓ SISTEM SIAP PRODUCTION${RESET}"
else
    echo -e "  ${RED}${BOLD}✗ ADA ${FAIL_COUNT} MODUL GAGAL — PERLU DIPERBAIKI${RESET}"
fi

echo -e "${BLUE}${BOLD}═══════════════════════════════════════════════════════════${RESET}\n"

exit $FAIL_COUNT
