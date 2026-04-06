# Google Authentication - Unregistered User Flow FIX

## Problem Statement
Ketika user mencoba login menggunakan akun Google yang belum terdaftar di sistem, mereka tidak menerima notifikasi yang jelas untuk menghubungi admin. Flow yang lama tidak menampilkan error message dengan baik.

## Root Causes Identified
1. **Hardcoded redirect**: GoogleController redirect ke `/login` dengan error message, bukan menggunakan route helper
2. **Session persistence issue**: Pada form_post OAuth callback, session flash data mungkin tidak persisted dengan beik
3. **Incomplete error handling**: Tidak ada fallback UI yang jelas untuk unregistered Google accounts
4. **Missing page prop**: Flash data tidak di-pass ke frontend Inertia props

## Changes Made

### 1. GoogleController.php
**File**: `/var/www/lawangsewu/app/Http/Controllers/Auth/GoogleController.php`

**Changes:**
- ✅ Redirect ke named route `route('access.pending')` daripada hardcoded `/login`
- ✅ Pass `reason` parameter: `unregistered` untuk akun baru, `pending` untuk existing inactive users, `error` untuk exceptions
- ✅ Enhanced logging dengan context:
  - Log saat user baru dari Google dibuat
  - Log saat inactive user mencoba login
  - Log errors dengan full stack trace
- ✅ Better error messages di frontend

**Flow:**
```
User clicks "Login dengan Google"
    ↓
Google OAuth callback (form_post)
    ↓
Check if email exists in database
    ├─ NO → Create new inactive user (is_active = false)
    │         Log: "New Google account created"
    │         reason = 'unregistered'
    │       
    └─ YES → Check if user is active
              ├─ YES → Auth::login() + redirect to dashboard
              └─ NO  → reason = 'pending'
              
Redirect to route('access.pending', ['reason' => $reason])
    ↓
Display PendingAccess.vue with context-appropriate message
```

### 2. PendingAccess.vue
**File**: `/var/www/lawangsewu/resources/js/Pages/Auth/PendingAccess.vue`

**Changes:**
- ✅ Enhanced UI dengan icons berdasarkan reason
- ✅ Context-specific messages:
  - `unregistered`: "Akun Google Anda telah berhasil terdaftar di sistem"
  - `pending`: "Akun Anda sudah tercatat, namun masih menunggu persetujuan"
  - `error`: Display actual error message
- ✅ Prominent contact email card dengan clickable link
- ✅ Added estimated activation time (1-2 jam kerja)
- ✅ Better visual hierarchy dan accessibility

### 3. HandleInertiaRequests.php
**File**: `/var/www/lawangsewu/app/Http/Middleware/HandleInertiaRequests.php`

**Changes:**
- ✅ Added `'flash'` to shared props untuk pass flash data ke frontend
- ✅ Allow PendingAccess.vue to access flash data jika ada

### 4. Routes (web.php)
**No changes needed** - `access.pending` route sudah ada dan properly configured

## User Experience Flow

### Scenario 1: Unregistered Google User (NEW ACCOUNT)
```
1. User clicks "Lanjutkan dengan Google"
2. Redirects ke Google authentication
3. Google callback ke handleGoogleCallback()
4. Email NOT found in database
5. ✅ New user created with is_active = false
6. → Redirect to /access/pending?reason=unregistered
7. Show: "Akun Google Anda Telah Terdaftar"
   Message: "Silakan menghubungi admin untuk mengaktifkan akses"
   Contact: Prominent email card dbprakom@gmail.com
   Estimate: 1-2 jam kerja
```

### Scenario 2: Existing Inactive Google User (PENDING APPROVAL)
```
1. User clicks "Lanjutkan dengan Google"
2. Redirects ke Google authentication
3. Google callback ke handleGoogleCallback()
4. Email found BUT is_active = false
5. → Redirect to /access/pending?reason=pending
6. Show: "Menunggu Persetujuan Admin"
   Message: "Akun Anda sudah tercatat, namun masih menunggu persetujuan"
   Contact: Prominent email card dbprakom@gmail.com
```

### Scenario 3: Error During Google Auth
```
1. User clicks "Lanjutkan dengan Google"
2. Google callback fails (network, invalid state, exception)
3. Exception caught in try-catch block
4. Error logged dengan full context
5. → Redirect to /access/pending?reason=error
6. Show: "Terjadi Kesalahan"
   Message: Display actual error message
   Contact: Prompt to contact admin
```

### Scenario 4: Active Google User (NORMAL LOGIN)
```
1. User clicks "Lanjutkan dengan Google"
2. Redirects ke Google authentication
3. Google callback ke handleGoogleCallback()
4. Email found AND is_active = true
5. ✅ Auth::login($user)
6. → Redirect to dashboard (or intended page)
```

## Admin Panel Integration
Users.vue admin page sudah punya filter untuk melihat pending Google accounts:
- ✅ Filter: "Hanya user Google menunggu persetujuan"
- ✅ Badge: "Daftar Baru" (created < 24 jam) atau "Antrian Lama"
- ✅ Checkbox untuk activate user
- ✅ Update button untuk save

**Admin dapat:**
1. Melihat semua pending Google users
2. Review nama dan email
3. Set role (viewer/operator/admin)
4. Activate user dengan checkbox
5. Save dengan Update button → user dapat login

## Logging
Untuk debug dan monitoring, check Laravel logs:
```bash
tail -f /var/www/lawangsewu/storage/logs/laravel.log
```

Setiap Google login attempt dicatat:
```
[timestamp] local.INFO: New Google account created {"user_id":123,"email":"user@gmail.com","google_id":"..."}
[timestamp] local.INFO: Google login attempt with inactive account {"user_id":123,"email":"user@gmail.com","is_new":true}
[timestamp] local.ERROR: Google login callback failed {"message":"...","type":"...","trace":"..."}
```

## Testing Checklist

- [ ] Test unregistered Google account login → shows PendingAccess page
- [ ] Test unregistered user can see contact email clearly
- [ ] Test admin can approve user from Users page
- [ ] Test approved user can login successfully
- [ ] Test error handling dengan fake exception
- [ ] Verify logs are created for all scenarios
- [ ] Test session persistence (form_post callback)
- [ ] Check responsive design on mobile
- [ ] Verify email link in PendingAccess opens mailto:

## Rollback Instructions
Jika ada issue, revert changes:
```bash
git checkout -- app/Http/Controllers/Auth/GoogleController.php
git checkout -- resources/js/Pages/Auth/PendingAccess.vue
git checkout -- app/Http/Middleware/HandleInertiaRequests.php
npm run build
php artisan optimize:clear
```

## Notes
- UI improvements untuk PendingAccess.vue
- Better error messages untuk end users
- Comprehensive logging untuk debugging
- Proper route naming convention
- Context-aware redirect based on account status
