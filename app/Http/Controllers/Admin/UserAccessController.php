<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleAccessAllowlist;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'allowlist' => GoogleAccessAllowlist::query()
                ->orderBy('email')
                ->get(),
            'superAdminEmail' => strtolower((string) config('auth.super_admin_email')),
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

        $email = strtolower($payload['email']);
        $superAdminEmail = strtolower((string) config('auth.super_admin_email'));
        $isSuperAdmin = $superAdminEmail !== '' && $email === $superAdminEmail;

        User::create([
            'name' => $payload['name'],
            'email' => $email,
            'password' => Hash::make($payload['password']),
            'role' => $isSuperAdmin ? 'admin' : $payload['role'],
            'is_active' => true,
            'is_superadmin' => $isSuperAdmin,
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
        $superAdminEmail = strtolower((string) config('auth.super_admin_email'));
        $isSuperAdmin = $superAdminEmail !== '' && strtolower((string) $user->email) === $superAdminEmail;

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

        if ($isSuperAdmin || $user->is_superadmin) {
            $payload['role'] = 'admin';
            $payload['is_active'] = true;
            $payload['is_superadmin'] = true;
        }

        $user->update($payload);

        return back()->with('status', 'Akses pengguna berhasil diperbarui.');
    }

    public function storeAllowlist(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:google_access_allowlist,email'],
            'note' => ['nullable', 'string', 'max:255'],
            'auto_activate' => ['nullable', 'boolean'],
        ]);

        GoogleAccessAllowlist::create([
            'email' => strtolower($payload['email']),
            'note' => $payload['note'] ?? null,
            'auto_activate' => (bool) ($payload['auto_activate'] ?? false),
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('status', 'Email Google berhasil ditambahkan ke daftar akses.');
    }

    public function updateAllowlist(Request $request, GoogleAccessAllowlist $entry): RedirectResponse
    {
        $payload = $request->validate([
            'auto_activate' => ['required', 'boolean'],
        ]);

        $entry->update([
            'auto_activate' => $payload['auto_activate'],
        ]);

        return back()->with('status', 'Status auto-aktif allowlist berhasil diperbarui.');
    }

    public function destroyAllowlist(GoogleAccessAllowlist $entry): RedirectResponse
    {
        $entry->delete();

        return back()->with('status', 'Email Google berhasil dihapus dari daftar akses.');
    }
}
