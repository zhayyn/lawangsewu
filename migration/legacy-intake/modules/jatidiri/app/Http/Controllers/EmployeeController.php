<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\EmployeeDirectorySyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        return view('employees.index', [
            'employees' => Employee::query()->orderBy('name')->paginate(30),
            'user' => $request->user(),
            'sourceLabels' => [
                'sikep-api' => 'SIKEP API',
                'sikep-portal-bezetting-docx' => 'SIKEP Bezetting',
                'sikep-portal-login' => 'SIKEP Portal',
                'bootstrap-import' => 'Arsip Awal',
            ],
        ]);
    }

    public function sync(Request $request, EmployeeDirectorySyncService $service): RedirectResponse
    {
        $user = $request->user();
        $role = (string) ($user->role ?? 'viewer');

        if (!in_array($role, ['superadmin', 'admin'], true)) {
            abort(403, 'Hanya superadmin/admin yang boleh sinkronisasi data pegawai.');
        }

        $source = (string) $request->input('source', 'sikep-portal');
        $result = $service->sync($source);

        $status = $result['ok'] ? 'sync_success' : 'sync_error';
        $message = $result['message'] . ' (inserted=' . $result['inserted'] . ', updated=' . $result['updated'] . ')';

        return redirect()->route('employees.index')->with($status, $message);
    }
}
