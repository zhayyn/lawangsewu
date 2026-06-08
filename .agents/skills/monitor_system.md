# 📡 Skill: monitor_system
# Agent: @monitor (Observability Agent)
# Trigger: Dipanggil via /status atau secara manual kapan saja

## Instruksi Eksekusi

Ketika skill ini diaktifkan, @monitor HARUS menjalankan systematic health check
sesuai prosedur di bawah. DILARANG melakukan fix — hanya observe dan recommend.

---

## Phase 1: COLLECT — Ambil Data

Jalankan check secara berurutan:

### 1.1 Framework & PHP
```bash
php artisan --version
php -v
composer --version
```

### 1.2 Service Health
```bash
# Reverb WebSocket
php artisan reverb:status

# Queue Worker
php artisan queue:monitor

# Supervisor Processes
sudo supervisorctl status
```

### 1.3 Database Connection
```bash
php artisan migrate:status
# atau
php artisan db:show
```

### 1.4 WaCaraka Runtime (External Server)
```bash
# Cek koneksi ke WA runtime di 192.168.88.44
curl -s --connect-timeout 5 http://192.168.88.44:8790/health
curl -s --connect-timeout 5 http://192.168.88.44:8791/health
```

### 1.5 External Dependencies
```bash
# SIPP DB connection (read-only)
php artisan tinker --execute="DB::connection('sipp')->getPdo()"

# Google OAuth
curl -s -o /dev/null -w "%{http_code}" https://accounts.google.com/.well-known/openid-configuration

# Tailscale (jika configured)
curl -s http://100.126.69.111:8080/health 2>/dev/null || echo "Tailscale not reachable"
```

### 1.6 System Resources
```bash
# Disk space
df -h / /var /home

# Memory
free -h

# CPU load
uptime
```

### 1.7 Error Logs
```bash
# Recent errors (last 100 lines)
tail -100 storage/logs/laravel.log | grep -E "(ERROR|CRITICAL|Alert)"

# Count by severity
grep -c "ERROR" storage/logs/laravel.log
grep -c "CRITICAL" storage/logs/laravel.log
```

### 1.8 Test Suite
```bash
php artisan test --compact 2>&1
```

---

## Phase 2: ANALYZE — Evaluasi Status

Klasifikasikan setiap finding:

### Severity Levels

| Level | Symbol | Definisi | Action |
|-------|--------|----------|--------|
| **Critical** | 🔴 | Service down, data loss risk, security breach | Fix sekarang |
| **Warning** | 🟡 | Degraded performance, non-critical error spike | Fix dalam 24 jam |
| **Info** | 🟢 | Healthy, informational | No action |

### Status Categories

```
🔴 CRITICAL:
- Database connection failed
- WA Caraka runtime unreachable (semua endpoint)
- Authentication broken (user tidak bisa login)
- Disk space > 90%
- Test suite regressions

🟡 WARNING:
- Queue worker stuck (> 100 failed jobs)
- Error log spike (> 10 errors/hour)
- Memory usage > 85%
- WA Caraka partial failure (primary down, fallback ok)
- Slow queries detected

🟢 HEALTHY:
- All services responding
- Error rate < 1%
- Test suite passing
- Resources normal
```

---

## Phase 3: REPORT — Output Template

### 📊 System Health Snapshot

```
═══════════════════════════════════════════════════════════════
                    SYSTEM HEALTH REPORT
═══════════════════════════════════════════════════════════════
Generated: [TIMESTAMP]
Scope: [FULL | PARTIAL | WA-CARAKA | DATABASE | SECURITY]
───────────────────────────────────────────────────────────────

📦 SERVICES
───────────────────────────────────────────────────────────────
┌────────────────────┬────────┬────────────────────────────┐
│ Service            │ Status │ Detail                     │
├────────────────────┼────────┼────────────────────────────┤
│ Laravel Framework  │   🟢   │ v13.x.x OK                 │
│ PHP                │   🟢   │ 8.3.x OK                   │
│ Reverb WebSocket   │   🟢   │ Running on :8080           │
│ Queue Worker       │   🟢   │ Processing normally        │
│ Supervisor         │   🟢   │ All processes UP           │
│ MySQL Database     │   🟢   │ Connected, XX tables       │
└────────────────────┴────────┴────────────────────────────┘

📡 EXTERNAL CONNECTIONS
───────────────────────────────────────────────────────────────
┌────────────────────┬────────┬────────────────────────────┐
│ Service            │ Status │ Detail                     │
├────────────────────┼────────┼────────────────────────────┤
│ WA Caraka Runtime  │   🟢   │ :8790 + :8791 both OK      │
│ SIPP Database      │   🟢   │ Read-only connected        │
│ Google OAuth       │   🟢   │ 200 OK                     │
│ Tailscale VPN      │   🟢   │ Reachable                  │
└────────────────────┴────────┴────────────────────────────┘

💾 SYSTEM RESOURCES
───────────────────────────────────────────────────────────────
┌────────────────────┬────────┬────────────────────────────┐
│ Resource           │ Usage  │ Status                     │
├────────────────────┼────────┼────────────────────────────┤
│ Disk (/)           │   45%  │ 🟢 OK                      │
│ Disk (/var)         │   62%  │ 🟢 OK                      │
│ Memory             │   68%  │ 🟢 OK                      │
│ CPU Load           │  1.42  │ 🟢 OK                      │
└────────────────────┴────────┴────────────────────────────┘

📝 RECENT ERRORS (Last 24h)
───────────────────────────────────────────────────────────────
Total Errors: XX
Last Error: [TIMESTAMP] - [MESSAGE]

🔴 CRITICAL Issues: X
🟡 WARNING Issues: X
🟢 Info: X

───────────────────────────────────────────────────────────────
🧪 TEST SUITE
───────────────────────────────────────────────────────────────
Passed: XX  |  Failed: XX  |  Skipped: X
Status: ✅ ALL PASSING / ⚠️ HAS FAILURES

═══════════════════════════════════════════════════════════════
                       OVERALL: ✅ HEALTHY
═══════════════════════════════════════════════════════════════
```

---

## Phase 4: RECOMMEND — Tindakan

Jika ada issue:

### 🔴 Critical → Wajib Fix Sekarang
```markdown
### 🔴 CRITICAL: [Title]

**Problem:** [Description]
**Impact:** [Who/what affected]
**Recommendation:**
1. [Immediate action]
2. [Verify fix]
```

### 🟡 Warning → Fix dalam 24 Jam
```markdown
### 🟡 WARNING: [Title]

**Problem:** [Description]
**Impact:** [Impact assessment]
**Recommendation:**
1. [Action 1]
2. [Action 2]
```

### 🟢 Info → No Action Required
```markdown
### 🟢 INFO: [Title]

[Informational note]
```

---

## Constraint Assertions

- ❌ **DILARANG** melakukan fix langsung — hanya observe dan report
- ❌ **DILARANG** mengubah konfigurasi sistem
- ✅ **WAJIB** berikan severity classification untuk setiap finding
- ✅ **WAJIB** gunakan template output di atas
- ✅ **WAJIB** cek WaCaraka runtime connectivity (192.168.88.44)
- ✅ **WAJIB** run test suite dan laporkan hasilnya
- ✅ **WAJIB** prioritaskan: Critical → Warning → Info

## Scope Options

| Trigger | Scope |
|---------|-------|
| `/status` | FULL — semua check |
| `/status wa` | WA-CARAKA — fokus WA Caraka runtime |
| `/status db` | DATABASE — DB + migrations |
| `/status resources` | SYSTEM — disk, memory, CPU |
| `/status tests` | TEST SUITE ONLY |
| `/status security` | SECURITY — errors, failed logins |

<!-- developed by dbprakom™ -->
