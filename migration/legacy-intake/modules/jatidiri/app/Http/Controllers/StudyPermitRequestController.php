<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\StudyPermitRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudyPermitRequestController extends Controller
{
    public function index(): View
    {
        return view('study-permits.index', [
            'requests' => StudyPermitRequest::query()->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('study-permits.create', [
            'employees' => Employee::query()->orderBy('name')->limit(300)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nip' => ['required', 'string', 'max:64'],
            'employee_name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'rank' => ['nullable', 'string', 'max:64'],
            'study_type' => ['required', 'string', 'max:120'],
            'study_program' => ['required', 'string', 'max:160'],
            'notes' => ['nullable', 'string'],
        ]);

        StudyPermitRequest::query()->create($validated + [
            'status' => 'draft',
            'source_system' => 'jatidiri-native',
            'source_payload' => ['created_from' => 'native-form'],
            'last_synced_at' => now(),
        ]);

        return redirect()->route('study-permits.index')->with('success', 'Draft izin belajar berhasil dibuat.');
    }
}