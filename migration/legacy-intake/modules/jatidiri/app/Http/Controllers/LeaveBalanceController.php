<?php

namespace App\Http\Controllers;

use App\Models\LeaveBalance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveBalanceController extends Controller
{
    /**
     * Display leave balance for all employees
     */
    public function index(Request $request): View
    {
        $query = LeaveBalance::query();

        // Filter by year
        if ($request->has('year')) {
            $query->where('year', $request->get('year'));
        } else {
            $query->where('year', now()->year);
        }

        // Filter by employee
        if ($request->has('employee_nip')) {
            $query->where('employee_nip', $request->get('employee_nip'));
        }

        $balances = $query->orderBy('employee_nip')->paginate(30);
        $employees = Employee::all()->pluck('name', 'nip');
        $currentYear = now()->year;

        return view('leaves.balances.index', compact('balances', 'employees', 'currentYear'));
    }

    /**
     * Show leave balance detail for employee
     */
    public function show(string $employeeNip, Request $request): View
    {
        $employee = Employee::where('nip', $employeeNip)->firstOrFail();
        
        $year = $request->get('year', now()->year);
        $balance = LeaveBalance::where('employee_nip', $employeeNip)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            $balance = LeaveBalance::create([
                'employee_nip' => $employeeNip,
                'year' => $year,
                'annual_quota' => 12,
                'used' => 0,
                'remaining' => 12,
                'source_system' => 'jatidiri-native',
            ]);
        }

        // Get all years for employee
        $years = LeaveBalance::where('employee_nip', $employeeNip)
            ->orderBy('year', 'desc')
            ->get()
            ->pluck('year')
            ->toArray();

        return view('leaves.balances.show', compact('employee', 'balance', 'years', 'year'));
    }

    /**
     * Show edit form for leave balance
     */
    public function edit(LeaveBalance $balance): View
    {
        $employee = Employee::where('nip', $balance->employee_nip)->first();

        return view('leaves.balances.edit', compact('balance', 'employee'));
    }

    /**
     * Update leave balance
     */
    public function update(Request $request, LeaveBalance $balance)
    {
        $validated = $request->validate([
            'annual_quota' => 'required|integer|min:0',
            'used' => 'required|integer|min:0',
            'carryover_from_previous' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        // Calculate remaining
        $remaining = $validated['annual_quota'] + $validated['carryover_from_previous'] - $validated['used'];

        $balance->update([
            ...$validated,
            'remaining' => $remaining,
            'last_updated_at' => now(),
        ]);

        return redirect()->route('leaves.balances.show', $balance->employee_nip)
            ->with('success', 'Sisa cuti berhasil diperbarui.');
    }

    /**
     * Initialize leave balance for all employees in a year
     */
    public function initializeYear(Request $request)
    {
        $year = $request->get('year', now()->year);
        $quota = $request->get('quota', 12);

        $employees = Employee::all();
        $created = 0;
        $updated = 0;

        foreach ($employees as $employee) {
            $balance = LeaveBalance::firstOrCreate(
                ['employee_nip' => $employee->nip, 'year' => $year],
                [
                    'annual_quota' => $quota,
                    'used' => 0,
                    'remaining' => $quota,
                    'source_system' => 'jatidiri-native',
                ]
            );

            if ($balance->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        return response()->json([
            'message' => "Inisialisasi cuti tahun {$year} selesai.",
            'created' => $created,
            'updated' => $updated,
            'total' => $created + $updated,
        ]);
    }

    /**
     * Carryover leave balance to next year
     */
    public function carryover(Request $request)
    {
        $currentYear = $request->get('year', now()->year);
        $nextYear = $currentYear + 1;
        $maxCarryover = $request->get('max_carryover', 3);

        $balances = LeaveBalance::where('year', $currentYear)->get();
        $carried = 0;

        foreach ($balances as $balance) {
            if ($balance->remaining > 0) {
                $carryoverDays = min($balance->remaining, $maxCarryover);

                LeaveBalance::firstOrCreate(
                    ['employee_nip' => $balance->employee_nip, 'year' => $nextYear],
                    [
                        'annual_quota' => 12,
                        'used' => 0,
                        'remaining' => 12,
                        'carryover_from_previous' => $carryoverDays,
                        'source_system' => 'jatidiri-native',
                    ]
                );

                $carried++;
            }
        }

        return response()->json([
            'message' => "Carryover cuti dari tahun {$currentYear} ke {$nextYear} selesai.",
            'carried' => $carried,
            'max_carryover' => $maxCarryover,
        ]);
    }

    /**
     * Bulk update leave balance
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer',
            'annual_quota' => 'required|integer|min:0',
        ]);

        $updated = LeaveBalance::where('year', $validated['year'])
            ->update(['annual_quota' => $validated['annual_quota']]);

        return response()->json([
            'message' => "Kuota cuti tahun {$validated['year']} berhasil diperbarui.",
            'updated' => $updated,
        ]);
    }
}
