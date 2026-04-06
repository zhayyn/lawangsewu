<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserAccessController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'google_id', 'role', 'is_active', 'email_verified_at', 'created_at'])
            ->orderByRaw('case when google_id is not null then 0 else 1 end')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => ['viewer', 'operator', 'admin'],
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:viewer,operator,admin'],
        ]);

        User::create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($payload['password']),
            'role' => $payload['role'],
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return back()->with('status', 'User baru berhasil didaftarkan secara manual.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $payload = $request->validate([
            'role' => ['required', 'in:viewer,operator,admin'],
            'is_active' => ['required', 'boolean'],
        ]);

        $actor = $request->user();

        // Prevent an admin from locking themselves out accidentally.
        if ($actor && $actor->id === $user->id && (! $payload['is_active'] || $payload['role'] !== 'admin')) {
            return back()->with('status', 'Akun admin yang sedang dipakai tidak boleh dinonaktifkan atau diturunkan role-nya.');
        }

        // Keep at least one active admin in the system.
        if (($user->role === 'admin' || $payload['role'] !== 'admin' || ! $payload['is_active']) && $user->id !== $actor?->id) {
            $activeAdminCount = User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->count();

            $wouldRemoveCurrentActiveAdmin = $user->role === 'admin' && $user->is_active && (! $payload['is_active'] || $payload['role'] !== 'admin');

            if ($wouldRemoveCurrentActiveAdmin && $activeAdminCount <= 1) {
                return back()->with('status', 'Minimal harus ada satu admin aktif.');
            }
        }

        $user->update($payload);

        return back()->with('status', 'Akses pengguna berhasil diperbarui.');
    }
}
