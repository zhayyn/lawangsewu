# Sistem Manajemen Cuti - Jatidiri

## Overview
Sistem manajemen permohonan dan pemberian cuti dengan format sesuai template "Cuti Master Semarang", termasuk tracking sisa cuti per tahun dan otomasi pembangkitan surat cuti.

## Fitur Utama

### 1. **Permohonan Cuti (Leave Requests)**
- Buat permohonan cuti dengan kategori lengkap:
  - Cuti Tahunan
  - Cuti Besar
  - Cuti Sakit
  - Cuti Melahirkan
  - Cuti Karena Alasan Penting
  - Cuti Di Luar Tanggungan Negara

- Fitur:
  - Tracking data pegawai, atasan langsung, alamat, telepon
  - Automatic menghitung hari kerja (mengeluarkan weekend)
  - Validasi sisa cuti untuk cuti tahunan
  - Status: pending → approved/rejected
  - Download surat cuti otomatis format template

### 2. **Manajemen Sisa Cuti (Leave Balances)**
- Per-pegawai per-tahun tracking:
  - Kuota tahunan (default 12 hari)
  - Carryover dari tahun sebelumnya (max 3 hari)
  - Total tersedia
  - Sudah dipakai
  - Sisa cuti
  
- Fitur:
  - Automatic deduct ketika approval
  - Carryover ke tahun berikutnya
  - Bulk initialization
  - Edit & manage sisa cuti

### 3. **Generator Surat Cuti**
- Template sesuai format asli dari Google Docs
- Variabel yang di-map:
  - Data pegawai (NIP, nama, jabatan, golongan, masa kerja)
  - Kategori cuti (dengan checkbox mark)
  - Alasan, durasi, periode
  - Alamat dan telepon
  - Sisa cuti per tahun (2024, 2025, 2026)
  - Data atasan (untuk approval)
  
- Format: Plain text (TXT) dengan placeholder yang mudah dimodifikasi

## Database Schema

### leave_requests (Update)
```sql
ALTER TABLE leave_requests ADD COLUMN (
    supervisor_nip VARCHAR(64),
    supervisor_name VARCHAR(255),
    address TEXT,
    phone VARCHAR(20),
    leave_category VARCHAR(100),
    decision_notes TEXT,
    approved_at TIMESTAMP,
    rejected_at TIMESTAMP
);
```

### leave_balances (Baru)
```sql
CREATE TABLE leave_balances (
    id BIGINT PRIMARY KEY,
    employee_nip VARCHAR(64) UNIQUE,
    year INT,
    annual_quota INT (default 12),
    used INT (default 0),
    remaining INT (default 12),
    carryover_from_previous INT (default 0),
    notes TEXT,
    source_system VARCHAR(64),
    source_payload JSON,
    last_updated_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Models

### LeaveRequest
```php
$request->employee->name              // Get employee details
$request->supervisor->name            // Get supervisor details
$request->leaveBalance()              // Relationship to balance
$request->getWorkingDaysAttribute()   // Calculate working days
$request->scopeApproved()             // Scope for approved
$request->scopeRejected()             // Scope for rejected
$request->scopePending()              // Scope for pending
```

### LeaveBalance
```php
$balance->employee->name              // Get employee relationship
$balance->deduct(5)                   // Deduct days
$balance->restore(5)                  // Restore days
LeaveBalance::getCurrentYearBalance($nip) // Get or create current year
```

### LeaveLetterGenerator (Service)
```php
$generator = new LeaveLetterGenerator();
$content = $generator->generateFromLeaveRequest($leaveRequest);
// or
$generator->setTemplate($customTemplate)->generateFromLeaveRequest($leaveRequest);
```

## Routes

### Leave Requests
```
GET    /leave-requests                        # List
GET    /leave-requests/create                 # Create form
POST   /leave-requests                        # Store
GET    /leave-requests/{id}                   # Show
GET    /leave-requests/{id}/edit              # Edit form
PUT    /leave-requests/{id}                   # Update
POST   /leave-requests/{id}/approve           # Approve
POST   /leave-requests/{id}/reject            # Reject
GET    /leave-requests/{id}/letter            # Download surat
DELETE /leave-requests/{id}                   # Delete
```

### Leave Balances
```
GET    /leave-balances                        # List all balances
GET    /leave-balances/{nip}                  # Show for employee
GET    /leave-balances/{id}/edit              # Edit form
PUT    /leave-balances/{id}                   # Update
POST   /leave-balances/initialize-year        # Initialize year
POST   /leave-balances/carryover              # Carryover to next year
POST   /leave-balances/bulk-update            # Bulk update
```

## Workflow

### 1. Membuat Permohonan Cuti
1. Navigate ke `/leave-requests/create`
2. Pilih pegawai, atasan langsung, kategori cuti
3. Isi alasan, tanggal mulai-selesai, alamat, telepon
4. Submit → sistem auto-check sisa cuti (untuk cuti tahunan)
5. Status: **pending**

### 2. Persetujuan Cuti
1. Admin review di `/leave-requests/{id}`
2. Klik "Setujui" → auto-deduct sisa cuti
3. Status: **approved** + approved_at timestamp

### 3. Penolakan Cuti
1. Admin klik "Tolak"
2. Input alasan penolakan
3. Status: **rejected** + rejected_at timestamp

### 4. Download Surat Cuti
1. Di detail permohonan, klik "Download Surat Cuti"
2. File TXT dengan nomor surat otomatis
3. Format sesuai template asli

### 5. Manajemen Sisa Cuti
1. Navigate ke `/leave-balances`
2. Filter by tahun atau pegawai
3. Edit sisa, kuota, atau carryover
4. Admin bisa initialize tahun baru atau carryover otomatis

## Controllers

### LeaveRequestController
```php
index()          # List permohonan
create()         # Form create
store()          # Save permohonan
show()           # Detail permohonan
edit()           # Form edit
update()         # Update permohonan
approve()        # Approve + deduct balance
reject()         # Reject dengan alasan
letter()         # Download surat
destroy()        # Delete (hanya non-approved/rejected)
```

### LeaveBalanceController
```php
index()              # List balances
show()               # Detail for employee
edit()               # Form edit
update()             # Update balance
initializeYear()     # Initialize all employees for a year
carryover()          # Carryover to next year (with max limit)
bulkUpdate()         # Bulk update quota for a year
```

## Views

### Leave Requests
- `leave-requests/index.blade.php` - List dengan filter status
- `leave-requests/create.blade.php` - Form full data
- `leave-requests/show.blade.php` - Detail + actions
- `leave-requests/edit.blade.php` - Edit form

### Leave Balances
- `leave-balances/index.blade.php` - List all balances
- `leave-balances/show.blade.php` - Detail per pegawai
- `leave-balances/edit.blade.php` - Edit dengan kalkulasi otomatis

## Template Surat Cuti

Template sudah di-embed di `LeaveLetterGenerator.php` dengan variabel:

```
<<now>>              - Tanggal saat ini (format: dd-mm-yyyy)
<<no>>               - Nomor surat (auto-generated)
<<month>>            - Bulan
<<year>>             - Tahun
<<nama>>             - Nama pegawai
<<nip1>>             - NIP pegawai
<<jab>>              - Jabatan
<<gol>>              - Golongan
<<masker>>           - Masa kerja
<<cuti_*_mark>>      - Checkbox mark untuk kategori cuti
<<alasan>>           - Alasan cuti
<<lama>>             - Lamanya cuti (hari kerja)
<<mulai>>            - Tanggal mulai (dd-mm-yyyy)
<<sampai>>           - Tanggal selesai (dd-mm-yyyy)
<<alamat>>           - Alamat selama cuti
<<telp>>             - Telepon
<<sisa_2024>>        - Sisa cuti 2024
<<sisa_2025>>        - Sisa cuti 2025
<<sisa_2026>>        - Sisa cuti 2026
<<nama2>>            - Nama atasan
<<nip2>>             - NIP atasan
```

## Validasi & Business Logic

### LeaveRequest Validation
- Employee NIP harus exist di employees table
- Supervisor NIP harus exist
- Leave category harus salah satu dari enum
- Start date ≤ end date
- Jika CUTI_TAHUNAN: sisa cuti harus ≥ durasi
- Tidak bisa edit jika sudah approved/rejected
- Tidak bisa delete jika sudah approved/rejected

### LeaveBalance Logic
- Auto-create untuk tahun baru dengan default 12 hari
- Carryover: maksimal 3 hari dari tahun sebelumnya
- Deduct otomatis saat approval (CUTI_TAHUNAN)
- Restore otomatis jika rejection terhadap CUTI_TAHUNAN (jika diperlukan custom logic)

## Working Days Calculation
- Hanya menghitung hari kerja (Monday-Friday)
- Exclude weekends
- Exclude holidays (jika perlu, tambah holiday table)

## Files Created/Modified

### Models
- `app/Models/LeaveRequest.php` (Updated)
- `app/Models/LeaveBalance.php` (New)

### Controllers
- `app/Http/Controllers/LeaveRequestController.php` (Updated)
- `app/Http/Controllers/LeaveBalanceController.php` (New)

### Services
- `app/Services/LeaveLetterGenerator.php` (New)

### Migrations
- `database/migrations/2026_03_17_000000_create_leave_balances_table.php` (New)
- `database/migrations/2026_03_17_000100_update_leave_requests_table.php` (New)

### Routes
- `routes/web.php` (Updated)

### Views
- `resources/views/leave-requests/index.blade.php` (Updated)
- `resources/views/leave-requests/create.blade.php` (Updated)
- `resources/views/leave-requests/show.blade.php` (New)
- `resources/views/leave-requests/edit.blade.php` (New)
- `resources/views/leave-balances/index.blade.php` (New)
- `resources/views/leave-balances/show.blade.php` (New)
- `resources/views/leave-balances/edit.blade.php` (New)

## API Usage Examples

### Create Leave Request
```php
$request = LeaveRequest::create([
    'employee_nip' => '196008031991031002',
    'supervisor_nip' => '197012121999031001',
    'leave_category' => 'CUTI_TAHUNAN',
    'leave_type' => 'Cuti Tahunan',
    'reason' => 'Keperluan pribadi',
    'start_date' => '2026-04-01',
    'end_date' => '2026-04-05',
    'address' => 'Jl. Kaligawe No. 32',
    'phone' => '081234567890',
    'status' => 'pending',
    'source_system' => 'jatidiri-native',
]);
```

### Approve & Deduct Balance
```php
$request->update(['status' => 'approved', 'approved_at' => now()]);
$balance = LeaveBalance::getCurrentYearBalance($request->employee_nip);
$balance->deduct((int)$request->duration_days);
```

### Generate Surat Cuti
```php
$generator = new LeaveLetterGenerator();
$content = $generator->generateFromLeaveRequest($request);
echo $content; // atau download
```

### Initialize Year
```php
$year = 2027;
$employees = Employee::all();
foreach ($employees as $emp) {
    LeaveBalance::firstOrCreate(
        ['employee_nip' => $emp->nip, 'year' => $year],
        ['annual_quota' => 12, 'used' => 0, 'remaining' => 12, 'source_system' => 'jatidiri-native']
    );
}
```

## Future Enhancements
1. **Approval Hierarchy** - Multi-level approval (atasan langsung, kepala satuan, pejabat)
2. **PDF Generation** - Export surat ke PDF bukan TXT
3. **Email Notification** - Notif approval/rejection ke email
4. **Holiday Calendar** - Exclude hari libur nasional dari perhitungan
5. **Cuti Darurat** - Fast-track approval untuk cuti mendadak
6. **Analytics** - Dashboard cuti per satuan kerja, trend penggunaan
7. **Integration** - Sync dengan SIKEP untuk cuti sakit dengan surat dokter

## Notes
- Template surat bisa di-customize di `LeaveLetterGenerator::$template`
- Untuk custom template: ubah property atau set via `->setTemplate()`
- Working days calculation menggunakan Carbon library
- Semua tanggal dalam format Y-m-d (database), display dd-m-yyyy (user)
