# Google Authentication - Testing Simulation

## Test Case 1: Unregistered Google Account (HAPPY PATH)

### User Action
1. Visit login page
2. Click "Lanjutkan dengan Google"
3. Authenticate with Google account (e.g., newuser@gmail.com)

### Backend Flow
```php
// GoogleController::handleGoogleCallback()

$googleUser = Socialite::driver('google')->user();
// Returns: { id, email: "newuser@gmail.com", name, avatar }

$user = User::where('email', 'newuser@gmail.com')->first();
// Returns: null (tidak ditemukan)

// CREATE NEW USER
$user = User::create([
    'email' => 'newuser@gmail.com',
    'name' => 'New User',
    'google_id' => 'google123456',
    'avatar' => 'https://...',
    'password' => Hash::make(random_string),
    'is_active' => false,           // 👈 INACTIVE
    'role' => 'viewer',
]);

// Log creation
Log::info('New Google account created', [
    'user_id' => 1,
    'email' => 'newuser@gmail.com',
    'google_id' => 'google123456',
]);

// REDIRECT dengan reason
return redirect()
    ->route('access.pending', ['reason' => 'unregistered'])
    ->with('flash', [
        'message' => 'Akun Google Anda telah dicatat. Admin akan mengaktifkan akses Anda segera.',
        'email' => 'dbprakom@gmail.com',
    ]);
```

### Frontend Result

```
URL: /access/pending?reason=unregistered

Page Title: "Akun Google Anda Terdaftar"

Message:
┌─────────────────────────────────────────────────────┐
│ ⏱️  Akun Google Anda Terdaftar                       │
│                                                     │
│ Akun Google Anda telah berhasil terdaftar di sistem │
│ Lawangsewu. Silakan menghubungi admin untuk        │
│ mengaktifkan akses Anda.                            │
│                                                     │
│ ┌─────────────────────────────────────────────────┐ │
│ │ HUBUNGI ADMIN                                   │ │
│ │ Dubes Prakom PA Semarang                        │ │
│ │ 📧 dbprakom@gmail.com  (clickable)              │ │
│ └─────────────────────────────────────────────────┘ │
│                                                     │
│ Estimasi aktivasi: 1-2 jam kerja                   │
│                                                     │
│ [Kembali ke Login]                                  │
└─────────────────────────────────────────────────────┘
```

---

## Test Case 2: Existing Inactive Google Account (PENDING)

### User Action
1. Previous user tries to login again
2. Click "Lanjutkan dengan Google"
3. Continue with same Google account

### Backend Flow
```php
$googleUser = Socialite::driver('google')->user();
// Same Google account

$user = User::where('email', 'newuser@gmail.com')->first();
// ✅ FOUND! (created from Case 1)

if (!$user->is_active) {  // is_active = false
    Log::info('Google login attempt with inactive account', [
        'user_id' => 1,
        'email' => 'newuser@gmail.com',
        'is_new' => false,  // ← Not new
    ]);

    return redirect()
        ->route('access.pending', ['reason' => 'pending'])
        ->with('flash', [...]);
}
```

### Frontend Result

```
URL: /access/pending?reason=pending

Page Title: "Menunggu Persetujuan Admin"

Message:
┌─────────────────────────────────────────────────────┐
│ ⏱️  Menunggu Persetujuan Admin                       │
│                                                     │
│ Akun Anda sudah tercatat di sistem, namun masih    │
│ menunggu persetujuan dari administrator Lawangsewu. │
│                                                     │
│ ┌─────────────────────────────────────────────────┐ │
│ │ HUBUNGI ADMIN                                   │ │
│ │ Dubes Prakom PA Semarang                        │ │
│ │ 📧 dbprakom@gmail.com  (clickable)              │ │
│ └─────────────────────────────────────────────────┘ │
│                                                     │
│ Estimasi aktivasi: 1-2 jam kerja                   │
│                                                     │
│ [Kembali ke Login]                                  │
└─────────────────────────────────────────────────────┘
```

---

## Test Case 3: Error During OAuth

### Trigger (simulated)
```php
// Simulate exception at Socialite::driver('google')->user()
throw new \Exception("Invalid OAuth state or network error");
```

### Backend Flow
```php
try {
    // ... oauth process
    throw new \Exception("Invalid OAuth state");
} catch (\Exception $e) {
    Log::error('Google login callback failed', [
        'message' => 'Invalid OAuth state',
        'type' => 'Exception',
        'trace' => '...',
    ]);

    return redirect()
        ->route('access.pending', ['reason' => 'error'])
        ->with('error', 'Login Google gagal: Invalid OAuth state');
}
```

### Frontend Result

```
URL: /access/pending?reason=error

Page Title: "Terjadi Kesalahan"

Message:
┌─────────────────────────────────────────────────────┐
│ ❌ Terjadi Kesalahan                                │
│                                                     │
│ Login Google gagal: Invalid OAuth state             │
│ Silakan coba lagi atau hubungi administrator.       │
│                                                     │
│ ┌─────────────────────────────────────────────────┐ │
│ │ HUBUNGI ADMIN                                   │ │
│ │ Dubes Prakom PA Semarang                        │ │
│ │ 📧 dbprakom@gmail.com  (clickable)              │ │
│ └─────────────────────────────────────────────────┘ │
│                                                     │
│ [Kembali ke Login]                                  │
└─────────────────────────────────────────────────────┘
```

---

## Test Case 4: Active Google User (SUCCESSFUL LOGIN)

### Setup
Admin telah mengaktifkan user dari Case 1

### User Action
1. Click "Lanjutkan dengan Google"
2. Authenticate with same Google account

### Backend Flow
```php
$googleUser = Socialite::driver('google')->user();
$user = User::where('email', 'newuser@gmail.com')->first();
// ✅ FOUND and is_active = true  (admin activated)

if (!$user->is_active) {
    // ← NOT executed because is_active = true
}

// ✅ LOGIN SUCCESSFUL
Auth::login($user);

Log::info('User successfully logged in via Google', [
    'user_id' => 1,
    'email' => 'newuser@gmail.com',
]);

return redirect()->intended(route('dashboard', absolute: false));
```

### Frontend Result

```
✅ Redirect to /dashboard

User sees:
- Dashboard page
- Navigation sidebar (based on user role)
- CCTV, Chat, Admin (if role = admin)
```

---

## Test Case 5: Form POST Fallback (Edge Case)

### Trigger
Cross-site form_post OAuth callback without session cookie (stateless fallback)

### Backend Flow
```php
try {
    $googleUser = Socialite::driver('google')->user();
} catch (InvalidStateException $stateException) {
    Log::warning('Google callback invalid state, retrying stateless', [
        'message' => 'InvalidStateException: ...',
        'type' => 'InvalidStateException',
    ]);
    
    // 👇 Fallback to stateless
    $googleUser = Socialite::driver('google')->stateless()->user();
    
    // Continue with normal flow...
}
```

### Result
✅ Despite missing session cookie, OAuth is completed stateless and flow continues normally

---

## Admin Approval Flow

### Admin Dashboard: `/admin/users`

```
┌─────────────────────────────────────────────────────────┐
│ Kelola Akses Pengguna                                   │
│                                                         │
│ [✓] Hanya user Google menunggu persetujuan  Total: 2   │
│                                                         │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ NAMA          | EMAIL           | PROVIDER | STATUS │ │
│ ├───────────────┼─────────────────┼──────────┼────────┤ │
│ │ New User      │ newuser@...     │ Google   │ 🟠     │ │
│ │               │                 │          │ Daftar │ │
│ │               │                 │          │ Baru   │ │
│ ├───────────────┼─────────────────┼──────────┼────────┤ │
│ │ Old User      │ olduser@...     │ Google   │ 🔴     │ │
│ │               │                 │          │ Antrian│ │
│ │               │                 │          │ Lama   │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                         │
```

### Admin Actions

**For Each User:**
1. Select Role: [viewer ▼]
2. Toggle Status: ☑️ Aktif (or ☐ Tertahan)
3. Click [Update] button

**Scenario:**
```
Admin selects "New User" row:
  - Role: [operator ▼]
  - Status: ☑️ Aktif  (checkbox checked)
  - Click [Update]
  
Result:
  ✅ User activated with "operator" role
  ✅ User can now login with Google
  ✅ Status flash: "Akses pengguna berhasil diperbarui."
```

---

## Logs to Monitor

View logs for debugging:
```bash
tail -f /var/www/lawangsewu/storage/logs/laravel.log | grep -i google
```

Expected log entries:
```
[2026-04-05 15:30:45] local.INFO: New Google account created 
  {"user_id":1,"email":"newuser@gmail.com","google_id":"google123...."}

[2026-04-05 15:31:12] local.INFO: Google login attempt with inactive account 
  {"user_id":1,"email":"newuser@gmail.com","is_new":false}

[2026-04-05 15:45:22] local.INFO: User successfully logged in via Google 
  {"user_id":1,"email":"newuser@gmail.com"}
```

---

## Summary of Improvements

| Aspect | Before | After |
|--------|--------|-------|
| **Error Message** | Generic `/login` with unclear error | Clear pending page with context |
| **Contact Info** | Message mentions email | Prominent clickable email card |
| **Visual Feedback** | Text-based only | Icons + color-coded messages |
| **Logging** | Minimal | Comprehensive with context |
| **Session Handling** | Potential loss on form_post | Fallback to stateless + proper redirect |
| **Route Pattern** | Hardcoded `/login` | Named route `route('access.pending')` |
| **Admin View** | Need to find user | Badge + filter "Daftar Baru" vs "Antrian Lama" |
| **UX Flow** | Confusing | Clear 4-state flow (unregistered/pending/error/success) |
