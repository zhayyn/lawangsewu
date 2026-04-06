# 📋 TASK PROMPTS - Untuk Km Kerjakan

## ⏰ STATUS: SIAP MULAI SEKARANG (5 April 2026, Malam)

---

## 🟢 TASK 1: Preparation untuk Socialite Integration
**Duration:** 1-2 jam | **Priority:** HIGH | **Status:** START NOW
**Blocker:** Menunggu aku deliver Socialite code (Senin pagi)

### Apa yang perlu km lakukan:

#### Step 1: Prepare Google OAuth Credentials
```
1. Buka: https://console.cloud.google.com
2. Create Project (atau gunakan existing)
3. Enable Google+ API
4. Create OAuth 2.0 credentials (Web application)
5. Set Authorized redirect URIs:
   - http://localhost:8000/auth/google/callback
   - http://satker-svr.local/auth/google/callback
6. Copy: Client ID & Client Secret
7. Save ke file aman (notes)
```

#### Step 2: Review Existing Auth Structure
```
cd /var/www/lawangsewu
- Lihat: routes/auth.php (existing auth routes)
- Lihat: config/services.php (provider config)
- Lihat: app/Models/User.php (user relation)
```

#### Step 3: Plan Login UI Layout
```
Design di kepala:
- Login page layout
- Google button placement
- Form layout (email/password fallback)
- Error display
- Mobile responsive

Dokumentasi: resources/views/auth-plan.txt
```

#### Step 4: Setup Laravel Breeze (if needed)
```
composer require laravel/breeze --dev
php artisan breeze:install vue
npm run build
```

**Deliverable Km:**
- ✅ Google OAuth credentials ready
- ✅ Auth routes reviewed
- ✅ Login UI layout planned
- ✅ Breeze installed

---

## 🟠 TASK 2: Chat UI Components
**Duration:** 3-4 jam | **Priority:** HIGH | **Status:** START MONDAY PM
**Blocker:** Menunggu aku deliver Reverb + Chat API

### Vue Components to Create:

#### resources/js/Components/Chat/ChatMessage.vue
```vue
Props:
- message: string
- sender: string
- timestamp: datetime
- isMine: boolean
- avatar: string

Display:
- Left/right aligned
- Bubble styling
- Time display
```

#### resources/js/Components/Chat/MessageInput.vue
```vue
Features:
- Text input
- Send button
- @keydown.enter to send
- Emit 'send' event
```

#### resources/js/Components/Chat/AliasSelector.vue
```vue
Features:
- Dropdown dari database
- Current alias display
- Edit/add buttons
```

#### resources/js/Components/Chat/MessageList.vue
```vue
Features:
- Scrollable list
- Auto-scroll to bottom
- Pagination for old messages
- Loading state
```

#### resources/js/Components/Chat/ChatBox.vue
```vue
Integration:
- Use all above components
- Fetch /api/chat/messages
- Listen to Reverb events
- Real-time updates
```

**Deliverable Km:**
- ✅ 5 Vue components created
- ✅ Styled dengan Tailwind
- ✅ Ready for API integration

---

## 🟡 TASK 3: CCTV Viewer Component
**Duration:** 2-3 jam | **Priority:** MEDIUM | **Status:** START TUESDAY AM
**Blocker:** Menunggu aku deliver CCTV API

### Vue Components to Create:

#### resources/js/Components/CCTV/CCTVViewer.vue
Main grid layout:
- Camera list dropdown
- Main viewing area
- Side panel dengan camera list
- Live/Recording indicators
- Timestamp

#### resources/js/Components/CCTV/CameraCard.vue
Card component:
- Camera name & location
- Live status
- Clickable
- Hover effects

#### resources/js/Components/CCTV/CameraStream.vue
Iframe with:
- Lazy loading
- Error handling
- Loading state
- Timeout handling

#### resources/js/Components/CCTV/CameraControls.vue
Optional controls:
- Play/pause
- Fullscreen
- Resolution selector

**Deliverable Km:**
- ✅ CCTV viewer component
- ✅ Camera cards styled
- ✅ iframe lazy loading
- ✅ Ready for real URLs

---

## 🔵 TASK 4: Dashboard Layout & Navigation
**Duration:** 2-3 jam | **Priority:** MEDIUM | **Status:** START WEDNESDAY AM
**Blocker:** Menunggu aku deliver Inertia boilerplate

### Create Components:

#### resources/js/Layouts/DashboardLayout.vue
```
- Header (navbar)
- Sidebar nav
- Main content area
- Footer
```

#### resources/js/Components/Sidebar.vue
Menu items:
- Dashboard
- Chat (locked unless operator+)
- CCTV (locked unless operator+)
- Antrian (for viewers)
- Reports (locked for admin+)
- Settings

Logic: Show/hide based on user role

#### resources/js/Components/Navbar.vue
- Logo
- Search
- User menu
- Notifications

#### resources/js/Components/UserMenu.vue
Dropdown:
- Profile
- Settings
- Change Alias
- Logout

#### resources/css/theme.css
- Primary color
- Secondary color
- Success/Warning/Error
- Dark mode support

**Deliverable Km:**
- ✅ Dashboard layout responsive
- ✅ Navigation with role-based access
- ✅ Proper styling
- ✅ Mobile friendly

---

## 🟣 TASK 5: Integration Testing & QA
**Duration:** 2-3 jam | **Priority:** HIGH | **Status:** START WEDNESDAY PM
**Blocker:** Semua backend delivered

### Test Checklist:

#### Google Login (10 items)
- [ ] Page loads
- [ ] Google button visible
- [ ] Click → OAuth redirect
- [ ] Authenticate with Google
- [ ] Callback works
- [ ] User created/linked
- [ ] Session set
- [ ] Redirect to dashboard
- [ ] Logout works
- [ ] Re-login works

#### Chat (10 items)
- [ ] User A sends message
- [ ] User B receives real-time
- [ ] User B replies
- [ ] User A receives reply real-time
- [ ] Alias display correct
- [ ] Rate limiting works
- [ ] Permission check (viewer cannot send?)
- [ ] No console errors
- [ ] Mobile responsive
- [ ] Performance OK

#### CCTV (8 items)
- [ ] Page loads
- [ ] Camera list shows
- [ ] Click camera → display
- [ ] Iframe loads lazy
- [ ] Multiple cameras work
- [ ] Permission check
- [ ] Mobile responsive
- [ ] No errors

#### Permissions (6 items)
- [ ] Viewer sees allowed features
- [ ] Operator sees operator+ features
- [ ] Admin sees admin+ features
- [ ] Superadmin sees all
- [ ] No permission bypass
- [ ] Proper error messages

### Create QA Report:
File: QA-REPORT-Sprint1.md

Include:
- Test environment
- Features tested
- Pass/fail status
- Screenshots
- Bugs found with severity
- Steps to reproduce
- Sign-off

**Deliverable Km:**
- ✅ All tests run
- ✅ QA report completed
- ✅ Bugs documented
- ✅ Ready for deploy

---

## 📅 RECOMMENDED TIMELINE

```
TONIGHT/TOMORROW:
→ TASK 1: Google OAuth prep (1-2 jam)

MONDAY:
→ While I code: TASK 2 Chat components (3-4 jam)

TUESDAY:
→ TASK 3 CCTV components (2-3 jam)
→ TASK 4 Dashboard layout (2-3 jam)

WEDNESDAY:
→ TASK 5 Integration testing (2-3 jam)
→ Final QA report

THURSDAY:
→ Ready to deploy! 🚀
```

---

## 🎯 SUCCESS CRITERIA

TASK 1 Done when:
- Google OAuth credentials secured
- Auth structure reviewed
- Login layout planned

TASK 2 Done when:
- 5 Vue components created
- Styled with Tailwind
- No console errors
- Structure clean

TASK 3 Done when:
- CCTV viewer component done
- Responsive design
- Ready for API integration

TASK 4 Done when:
- Dashboard layout responsive
- Navigation with role-based access
- Theme colors applied
- Mobile friendly

TASK 5 Done when:
- All tests passed or documented
- QA report completed with screenshots
- Bugs found & assigned severity
- Sign-off ready

---

## 📞 QUESTIONS?

If stuck:
1. Check existing components in `resources/js/Components/`
2. Tailwind docs: https://tailwindcss.com
3. Vue docs: https://vuejs.org
4. Inertia docs: https://inertiajs.com

I'll provide API docs once backend code ready!

---

**START NOW WITH TASK 1!** ✅
