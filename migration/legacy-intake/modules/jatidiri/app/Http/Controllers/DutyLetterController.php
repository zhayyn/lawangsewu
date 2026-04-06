<?php

namespace App\Http\Controllers;

use App\Models\DutyLetter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DutyLetterController extends Controller
{
    public function index(): View
    {
        return view('duty-letters.index', [
            'letters' => DutyLetter::query()->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('duty-letters.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'letter_number' => ['nullable', 'string', 'max:128'],
            'letter_date' => ['required', 'date'],
            'purpose' => ['required', 'string'],
            'destination_agency' => ['required', 'string', 'max:160'],
            'destination_city' => ['required', 'string', 'max:120'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'signing_officer' => ['nullable', 'string', 'max:120'],
            'dipa_code' => ['nullable', 'string', 'max:32'],
        ]);

        DutyLetter::query()->create($validated + [
            'source_system' => 'jatidiri-native',
            'source_payload' => ['created_from' => 'native-form'],
            'last_synced_at' => now(),
        ]);

        return redirect()->route('duty-letters.index')->with('success', 'Draft surat tugas berhasil dibuat.');
    }
}