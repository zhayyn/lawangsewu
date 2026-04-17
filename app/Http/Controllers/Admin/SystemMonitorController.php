<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemMonitorService;
use App\Support\LawangsewuPortal;
use Inertia\Inertia;
use Inertia\Response;

class SystemMonitorController extends Controller
{
    public function index(SystemMonitorService $service): Response
    {
        return Inertia::render('Admin/SystemMonitor', [
            'appMeta' => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'snapshot' => $service->snapshot(),
        ]);
    }
}
