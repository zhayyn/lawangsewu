<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DutyLetterController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\PortalSsoController;
use App\Http\Controllers\ServiceCenterController;
use App\Http\Controllers\StudyPermitRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/sso/consume', [PortalSsoController::class, 'consume'])->name('sso.consume');

Route::middleware(['portal.sso'])->group(function (): void {
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/employees/sync', [EmployeeController::class, 'sync'])->name('employees.sync');

    Route::get('/services', ServiceCenterController::class)->name('services.index');

    // Leave Requests
    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::get('/leave-requests/create', [LeaveRequestController::class, 'create'])->name('leave-requests.create');
    Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('leave-requests.show');
    Route::get('/leave-requests/{leaveRequest}/edit', [LeaveRequestController::class, 'edit'])->name('leave-requests.edit');
    Route::put('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'update'])->name('leave-requests.update');
    Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::post('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
    Route::get('/leave-requests/{leaveRequest}/letter', [LeaveRequestController::class, 'letter'])->name('leave-requests.letter');
    Route::delete('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->name('leave-requests.destroy');

    // Leave Balances
    Route::get('/leave-balances', [LeaveBalanceController::class, 'index'])->name('leave-balances.index');
    Route::get('/leave-balances/{employeeNip}', [LeaveBalanceController::class, 'show'])->name('leave-balances.show');
    Route::get('/leave-balances/{leaveBalance}/edit', [LeaveBalanceController::class, 'edit'])->name('leave-balances.edit');
    Route::put('/leave-balances/{leaveBalance}', [LeaveBalanceController::class, 'update'])->name('leave-balances.update');
    Route::post('/leave-balances/initialize-year', [LeaveBalanceController::class, 'initializeYear'])->name('leave-balances.initialize-year');
    Route::post('/leave-balances/carryover', [LeaveBalanceController::class, 'carryover'])->name('leave-balances.carryover');
    Route::post('/leave-balances/bulk-update', [LeaveBalanceController::class, 'bulkUpdate'])->name('leave-balances.bulk-update');

    Route::get('/study-permits', [StudyPermitRequestController::class, 'index'])->name('study-permits.index');
    Route::get('/study-permits/create', [StudyPermitRequestController::class, 'create'])->name('study-permits.create');
    Route::post('/study-permits', [StudyPermitRequestController::class, 'store'])->name('study-permits.store');

    Route::get('/duty-letters', [DutyLetterController::class, 'index'])->name('duty-letters.index');
    Route::get('/duty-letters/create', [DutyLetterController::class, 'create'])->name('duty-letters.create');
    Route::post('/duty-letters', [DutyLetterController::class, 'store'])->name('duty-letters.store');
});
