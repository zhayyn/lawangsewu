<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\Employee;
use App\Services\LeaveLetterGenerator;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class LeaveRequestController extends Controller
{
    protected LeaveLetterGenerator $letterGenerator;

    public function __construct(LeaveLetterGenerator $letterGenerator)
    {
        $this->letterGenerator = $letterGenerator;
    }

    public function index(Request $request): View
    {
        $query = LeaveRequest::query();

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('employee_nip')) {
            $query->where('employee_nip', $request->get('employee_nip'));
        }

        return view('leave-requests.index', [
            'requests' => $query->latest()->paginate(20),
            'statuses' => ['pending', 'approved', 'rejected'],
        ]);
    }

    public function create(): View
    {
        $employees = Employee::orderBy('name')->get();
        $leaveCategories = [
            'CUTI_TAHUNAN' => 'Cuti Tahunan',
            'CUTI_BESAR' => 'Cuti Besar',
            'CUTI_SAKIT' => 'Cuti Sakit',
            'CUTI_MELAHIRKAN' => 'Cuti Melahirkan',
            'CUTI_ALASAN_PENTING' => 'Cuti Karena Alasan Penting',
            'CUTI_DILUAR_TANGGUNGAN' => 'Cuti Di Luar Tanggungan Negara',
        ];

        return view('leave-requests.create', compact('employees', 'leaveCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_nip' => 'required|string|exists:employees,nip',
            'supervisor_nip' => 'nullable|string|exists:employees,nip',
            'leave_category' => 'required|string|in:CUTI_TAHUNAN,CUTI_BESAR,CUTI_SAKIT,CUTI_MELAHIRKAN,CUTI_ALASAN_PENTING,CUTI_DILUAR_TANGGUNGAN',
            'leave_type' => 'required|string',
            'reason' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $employee = Employee::where('nip', $validated['employee_nip'])->first();
        $workingDays = $this->calculateWorkingDays($validated['start_date'], $validated['end_date']);

        // Check leave balance if CUTI_TAHUNAN
        if ($validated['leave_category'] === 'CUTI_TAHUNAN') {
            $balance = LeaveBalance::getCurrentYearBalance($validated['employee_nip']);
            if ($workingDays > $balance->remaining) {
                return back()->withErrors([
                    'leave_balance' => "Sisa cuti hanya {$balance->remaining} hari kerja, tidak cukup untuk {$workingDays} hari kerja."
                ]);
            }
        }

        $leaveRequest = LeaveRequest::create([
            ...$validated,
            'employee_name' => $employee->name,
            'supervisor_name' => Employee::where('nip', $validated['supervisor_nip'])->first()?->name,
            'duration_days' => $workingDays,
            'status' => 'pending',
            'source_system' => 'jatidiri-native',
            'source_payload' => ['created_from' => 'native-form'],
            'last_synced_at' => now(),
        ]);

        return redirect()->route('leave-requests.show', $leaveRequest->id)
            ->with('success', 'Permohonan cuti berhasil dibuat.');
    }

    public function show(LeaveRequest $leaveRequest): View
    {
        $employee = Employee::where('nip', $leaveRequest->employee_nip)->first();
        $supervisor = $leaveRequest->supervisor_nip 
            ? Employee::where('nip', $leaveRequest->supervisor_nip)->first()
            : null;

        return view('leave-requests.show', compact('leaveRequest', 'employee', 'supervisor'));
    }

    public function edit(LeaveRequest $leaveRequest): View
    {
        if ($leaveRequest->approved_at || $leaveRequest->rejected_at) {
            abort(403, 'Tidak dapat mengubah permohonan yang sudah diputuskan.');
        }

        $employees = Employee::orderBy('name')->get();
        $leaveCategories = [
            'CUTI_TAHUNAN' => 'Cuti Tahunan',
            'CUTI_BESAR' => 'Cuti Besar',
            'CUTI_SAKIT' => 'Cuti Sakit',
            'CUTI_MELAHIRKAN' => 'Cuti Melahirkan',
            'CUTI_ALASAN_PENTING' => 'Cuti Karena Alasan Penting',
            'CUTI_DILUAR_TANGGUNGAN' => 'Cuti Di Luar Tanggungan Negara',
        ];

        return view('leave-requests.edit', compact('leaveRequest', 'employees', 'leaveCategories'));
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->approved_at || $leaveRequest->rejected_at) {
            return back()->withErrors(['message' => 'Tidak dapat mengubah permohonan yang sudah diputuskan.']);
        }

        $validated = $request->validate([
            'employee_nip' => 'required|string|exists:employees,nip',
            'supervisor_nip' => 'nullable|string|exists:employees,nip',
            'leave_category' => 'required|string|in:CUTI_TAHUNAN,CUTI_BESAR,CUTI_SAKIT,CUTI_MELAHIRKAN,CUTI_ALASAN_PENTING,CUTI_DILUAR_TANGGUNGAN',
            'leave_type' => 'required|string',
            'reason' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $employee = Employee::where('nip', $validated['employee_nip'])->first();
        $workingDays = $this->calculateWorkingDays($validated['start_date'], $validated['end_date']);

        $leaveRequest->update([
            ...$validated,
            'employee_name' => $employee->name,
            'supervisor_name' => Employee::where('nip', $validated['supervisor_nip'])->first()?->name,
            'duration_days' => $workingDays,
        ]);

        return redirect()->route('leave-requests.show', $leaveRequest->id)
            ->with('success', 'Permohonan cuti berhasil diperbarui.');
    }

    public function approve(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->approved_at) {
            return back()->withErrors(['message' => 'Permohonan sudah disetujui.']);
        }

        $leaveRequest->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // Deduct from leave balance
        if ($leaveRequest->leave_category === 'CUTI_TAHUNAN') {
            $balance = LeaveBalance::getCurrentYearBalance($leaveRequest->employee_nip);
            $balance->deduct((int)$leaveRequest->duration_days);
        }

        return back()->with('success', 'Permohonan cuti disetujui.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'decision_notes' => 'required|string',
        ]);

        if ($leaveRequest->rejected_at) {
            return back()->withErrors(['message' => 'Permohonan sudah ditolak.']);
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'decision_notes' => $validated['decision_notes'],
        ]);

        return back()->with('success', 'Permohonan cuti ditolak.');
    }

    public function letter(LeaveRequest $leaveRequest)
    {
        $content = $this->letterGenerator->generateFromLeaveRequest($leaveRequest);

        return response($content)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="surat_cuti_' . $leaveRequest->employee_nip . '_' . now()->format('YmdHis') . '.txt"');
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->approved_at || $leaveRequest->rejected_at) {
            return back()->withErrors(['message' => 'Tidak dapat menghapus permohonan yang sudah diputuskan.']);
        }

        $leaveRequest->delete();

        return redirect()->route('leave-requests.index')
            ->with('success', 'Permohonan cuti berhasil dihapus.');
    }

    private function calculateWorkingDays($startDate, $endDate): int
    {
        $count = 0;
        $current = Carbon::parse($startDate)->copy();
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            if ($current->isWeekday()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }
}