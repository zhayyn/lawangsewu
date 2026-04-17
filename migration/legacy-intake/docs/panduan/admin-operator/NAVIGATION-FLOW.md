# 🗺️ Lawangsewu Portal Navigation Flow

## Entry Points & Redirects

### 1. **Root Domain Access**
```
User: https://lawangsewu.pa-semarang.go.id
    ↓
File: /var/www/html/lawangsewu/index.php (NEW)
    ↓
Sessions Check: $_SESSION['gateway_user']?
    ├─ YES (logged in) → /lawangsewu/gateway/index (Dashboard)
    └─ NO (not logged in) → /lawangsewu/gateway/login?return=/lawangsewu/gateway/index
```

### 2. **Login Flow**
```
User: https://lawangsewu.pa-semarang.go.id/gateway/login
    ↓
File: /var/www/html/lawangsewu/gateway/login.php (IMPROVED)
    ↓
New Features:
  ✓ Info box showing post-login destination (Dashboard + available modules)
  ✓ Return parameter automatically maintained
  ✓ Navigation links to homepage
  ✓ Better error messages
    ↓
Submit Credentials
    ↓
POST handler: gateway_attempt_login()
    ├─ SUCCESS → Redirect to /lawangsewu/gateway/index
    └─ FAILURE → Show error, re-display login form
```

### 3. **Dashboard Access**
```
User: https://lawangsewu.pa-semarang.go.id/gateway/index
    ↓
File: /var/www/html/lawangsewu/gateway/index.php
    ↓
Auth Check: gateway_require_login()
    ├─ If NOT logged in → Redirect to login with return=/gateway/index
    └─ If logged in → Display dashboard with 3 new module buttons:
        ✓ 📢 Blasting (Admin only)
        ✓ 📩 Pengaduan (Admin/Operator)
        ✓ 💬 Konsultasi (Admin/Operator)
```

### 4. **New Module Access Paths**
```
From Dashboard navigation:

Blasting:
  /lawangsewu/wa-caraka/dashboard-ci4-admin/blast
  → BlastController@index (list all jobs)
  → BlastController@create (new batch form)
  → BlastController@show/:id (job status + results)
  → BlastController@cancel (cancel running job)

Pengaduan (Complaints):
  /lawangsewu/wa-caraka/dashboard-ci4-admin/pengaduan
  → PengaduanController@index (tab-filtered inbox)
  → PengaduanController@show/:id (detail + WA reply)
  → PengaduanController@update/:id (change status, send reply)

Konsultasi (Q&A):
  /lawangsewu/wa-caraka/dashboard-ci4-admin/konsultasi
  → KonsultasiController@index (tab-filtered inbox)
  → KonsultasiController@show/:id (detail + answer form)
  → KonsultasiController@update/:id (answer, send reply, auto-close)
```

## Session & Authentication

**Session Name:** `lawangsewu_gateway_session`

**Session Variable:** `$_SESSION['gateway_user']`
```php
Array(
    [full_name] => "Nama Pengguna"
    [username] => "username"
    [role] => "admin|operator|superadmin"  // from gateway SSO
)
```

**Auth Functions (gateway/bootstrap.php):**
- `gateway_is_logged_in()` → Check if session exists
- `gateway_require_login()` → Redirect to login if not authenticated
- `gateway_attempt_login($username, $password)` → Perform authentication
- `gateway_auth_user()` → Get current user from session
- `gateway_normalize_return_path($path)` → Validate & sanitize return URL

## Files Changed/Created

| File | Status | Purpose |
|------|--------|---------|
| `/var/www/html/lawangsewu/index.php` | **NEW** | Root entry point with session routing |
| `/var/www/html/lawangsewu/gateway/login.php` | **IMPROVED** | Enhanced login form with info box & better UX |
| `/var/www/html/lawangsewu/gateway/index.php` | ✓ Existing | Dashboard (already supports new modules) |
| `wa-caraka/dashboard-ci4-admin/app/Views/dashboard/index.php` | **UPDATED** | Dashboard nav with 3 new module buttons |
| `wa-caraka/dashboard-ci4-admin/app/Controllers/{Blast,Pengaduan,Konsultasi}Controller.php` | **NEW** | Module controllers (3 files) |
| `wa-caraka/dashboard-ci4-admin/app/Models/Wacaraka{Blast,BlastRecipient,Pengaduan,Konsultasi}Model.php` | **NEW** | Database models (4 files) |
| `wa-caraka/dashboard-ci4-admin/app/Views/{blast,pengaduan,konsultasi}/*.php` | **NEW** | Module views (7 files) |
| `wa-caraka/dashboard-ci4-admin/app/Config/Routes.php` | **UPDATED** | Added 11 new routes |
| `wa-caraka/server.mjs` | **UPDATED** | Added blast endpoints + async job queue |
| `wa-caraka/sql/db_wacaraka.sql` | **UPDATED** | Added 4 new database tables |

## Testing Checklist

- [ ] Access https://lawangsewu.pa-semarang.go.id → redirects to {login or dashboard}
- [ ] Access https://lawangsewu.pa-semarang.go.id/gateway/login → shows improved login form
- [ ] Login with valid credentials → redirects to dashboard
- [ ] Dashboard shows all 3 new buttons for admin account
- [ ] Click "📢 Blasting" → opens blast module
- [ ] Click "📩 Pengaduan" → opens complaints module  
- [ ] Click "💬 Konsultasi" → opens Q&A module
- [ ] Test blast creation with CSV recipient list
- [ ] Test pengaduan status filtering and WA reply
- [ ] Test konsultasi answer & auto-close on selesai status

## Performance Notes

- No database queries on root redirect (only session check)
- Root redirect uses PHP `header()` with 302 status (temporary redirect)
- Login page return URL is validated before use (XSS prevention)
- Dashboard navigation is server-side rendered (no runtime lookups)

## User Experience Improvements

✅ **Before:**
- No clear entry point at root domain
- Unclear where login would take user
- No visual indication of available modules

✅ **After:**
- Seamless root domain access
- Clear info box showing "After login: Dashboard + Blasting/Pengaduan/Konsultasi"
- Navigation links to homepage from login page
- 3 prominent buttons on dashboard for new modules
- Responsive design works on mobile/desktop
