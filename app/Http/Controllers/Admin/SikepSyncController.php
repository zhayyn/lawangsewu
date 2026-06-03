<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\SikepSyncService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SikepSyncController extends Controller
{
    public function index()
    {
        $lastSynced = Employee::max('last_synced_at');
        $totalEmployees = Employee::count();

        return Inertia::render('Admin/SikepSync', [
            'lastSynced' => $lastSynced,
            'totalEmployees' => $totalEmployees,
        ]);
    }

    public function sync(Request $request, SikepSyncService $syncService)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $result = $syncService->sync('sikep-portal', $request->username, $request->password);

        if ($result['ok']) {
            return back()->with('success', $result['message'] . '. Inserted: ' . $result['inserted'] . ', Updated: ' . $result['updated']);
        }

        return back()->withErrors(['error' => $result['message']]);
    }
}
