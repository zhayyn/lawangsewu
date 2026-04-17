<?php

namespace App\Http\Controllers;

use App\Models\DutyLetter;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\StudyPermitRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'user' => $user,
            'employeeCount' => Employee::query()->count(),
            'leaveCount' => LeaveRequest::query()->count(),
            'studyCount' => StudyPermitRequest::query()->count(),
            'dutyCount' => DutyLetter::query()->count(),
            'lastSync' => Employee::query()->max('last_synced_at'),
            'sourceBreakdown' => Employee::query()
                ->selectRaw('source_system, COUNT(*) as total')
                ->groupBy('source_system')
                ->orderByDesc('total')
                ->get(),
            'recentLeaves' => LeaveRequest::query()->latest()->limit(5)->get(),
            'recentDuties' => DutyLetter::query()->latest()->limit(5)->get(),
        ]);
    }
}
