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
            'appMeta' => \App\Support\LawangsewuPortal::appMeta(),
            'navGroups' => \App\Support\LawangsewuPortal::navGroups(),
        ]);
    }

    public function sync(Request $request, SikepSyncService $syncService)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,docx,doc|max:10240',
        ], [
            'file.required' => 'Silakan pilih file untuk diunggah.',
            'file.mimes' => 'Format file harus berupa CSV atau DOCX.',
            'file.max' => 'Ukuran file maksimal adalah 10MB.',
        ]);

        $result = $syncService->syncFromFile($request->file('file'));

        if ($result['ok']) {
            return back()->with('success', $result['message'] . '. Inserted: ' . $result['inserted'] . ', Updated: ' . $result['updated']);
        }

        return back()->withErrors(['error' => $result['message']]);
    }
}
