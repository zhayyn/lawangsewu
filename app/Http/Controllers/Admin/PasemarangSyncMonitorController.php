<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\LawangsewuPortal;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class PasemarangSyncMonitorController extends Controller
{
    public function index(): Response
    {
        $jsonPath = storage_path('app/pasemarang_sync.json');
        $logPath = storage_path('app/pasemarang_sync.log');
        
        $status = [
            'last_run' => 'Belum pernah dijalankan',
            'status' => 'UNKNOWN',
            'duration' => '-',
            'rw_disk' => 'Unknown',
            's9_disk' => 'Unknown',
        ];
        
        if (File::exists($jsonPath)) {
            $data = json_decode(File::get($jsonPath), true);
            if ($data) {
                $status = array_merge($status, $data);
            }
        }
        
        $log = [];
        if (File::exists($logPath)) {
            $logContent = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($logContent)) {
                $log = array_slice($logContent, -100);
            }
        }
        
        return Inertia::render('Admin/PasemarangSyncMonitor', [
            'appMeta' => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'syncStatus' => $status,
            'syncLog' => $log,
        ]);
    }
}
