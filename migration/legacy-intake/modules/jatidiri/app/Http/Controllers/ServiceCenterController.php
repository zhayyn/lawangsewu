<?php

namespace App\Http\Controllers;

use App\Models\DutyLetter;
use App\Models\LeaveRequest;
use App\Models\StudyPermitRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceCenterController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('services.index', [
            'user' => $request->user(),
            'leaveCount' => LeaveRequest::query()->count(),
            'studyCount' => StudyPermitRequest::query()->count(),
            'dutyCount' => DutyLetter::query()->count(),
        ]);
    }
}