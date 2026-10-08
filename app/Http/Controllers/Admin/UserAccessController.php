<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeaturePermission;
use App\Models\GoogleAccessAllowlist;
use App\Models\LoginHistory;
use App\Models\PermissionAuditLog;
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
        $actor = $request->user();
        $actorIsSuperAdmin = (bool) ($actor?->isSuperAdmin());
        $roles = $actorIsSuperAdmin
            ? ['viewer', 'operator', 'useradmin', 'admin']
            : ['viewer', 'operator', 'useradmin'];

        $users = User::query()
            ->select(['id', 'name', 'alias', 'email', 'google_id', 'role', 'is_active', 'email_verified_at', 'created_at'])
            ->orderByRaw('case when google_id is not null then 0 else 1 end')
            ->orderByDesc('created_at')
            ->get();

        $users = $users->map(function (User $user) use ($actorIsSuperAdmin) {
            $canManage = $actorIsSuperAdmin
                || (! $user->isSuperAdmin() && $user->role !== 'admin');

            $entry = [
                'id' => $user->id,
                'name' => $user->name,
                'alias' => $user->alias,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,
                'is_superadmin' => (bool) $user->isSuperAdmin(),
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'can_manage' => $canManage,
            ];

            if ($actorIsSuperAdmin) {
                $entry['google_id'] = $user->google_id;
            }

            return $entry;
        })->values();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => $roles,
            'featureCatalog' => array_values(config('features.features', [])),
            'roleFeaturePermissions' => $this->roleFeaturePermissions(),
            'userFeaturePermissions' => FeaturePermission::query()
                ->whereNotNull('user_id')
                ->get(['user_id', 'feature_key', 'enabled'])
                ->map(fn (FeaturePermission $entry) => [
                    'user_id' => $entry->user_id,
                    'feature_key' => $entry->feature_key,
                    'enabled' => (bool) $entry->enabled,
                ])
                ->values(),
            'allowlist' => (function () {
                // Ambil semua email user yang sudah terdaftar sekaligus (1 query, bukan N+1)
                $registeredUsers = User::query()
                    ->select(['email', 'is_active', 'name'])
                    ->get()
                    ->keyBy(fn (User $u) => strtolower(trim($u->email)));

                return GoogleAccessAllowlist::query()
                    ->orderBy('email')
                    ->get()
                    ->map(function (GoogleAccessAllowlist $entry) use ($registeredUsers) {
                        $emailKey    = strtolower(trim($entry->email));
                        $matchedUser = $registeredUsers->get($emailKey);
                        return [
                            'id'             => $entry->id,
                            'email'          => $entry->email,
                            'note'           => $entry->note,
                            'auto_activate'  => (bool) $entry->auto_activate,
                            'created_at'     => $entry->created_at,
                            // Info apakah sudah jadi user aktif
                            'is_registered'  => $matchedUser !== null,
                            'is_active_user' => $matchedUser ? (bool) $matchedUser->is_active : false,
                            'user_name'      => $matchedUser?->name,
                        ];
                    })
                    ->values();
            })(),
            'loginHistories' => LoginHistory::with('user:id,name,email')
                ->latest('logged_in_at')
                ->take(100)
                ->get(),
            'permissionAuditLogs' => PermissionAuditLog::query()
                ->with(['actor:id,name,email', 'targetUser:id,name,email'])
                ->latest('created_at')
                ->take(120)
                ->get()
                ->map(fn (PermissionAuditLog $log) => [
                    'id' => $log->id,
                    'scope' => $log->scope,
                    'action' => $log->action,
                    'role' => $log->role,
                    'feature_key' => $log->feature_key,
                    'old_enabled' => $log->old_enabled,
                    'new_enabled' => $log->new_enabled,
                    'ip_address' => $log->ip_address,
                    'actor' => $log->actor ? [
                        'id' => $log->actor->id,
                        'name' => $log->actor->name,
                        'email' => $log->actor->email,
                    ] : null,
                    'target_user' => $log->targetUser ? [
                        'id' => $log->targetUser->id,
                        'name' => $log->targetUser->name,
                        'email' => $log->targetUser->email,
                    ] : null,
                    'created_at' => $log->created_at,
                ])
                ->values(),
            'superAdminEmail' => strtolower((string) config('auth.super_admin_email')),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $actorIsSuperAdmin = (bool) ($actor?->isSuperAdmin());
        $allowedRoles = $actorIsSuperAdmin
            ? 'viewer,operator,useradmin,admin'
            : 'viewer,operator,useradmin';

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:'.$allowedRoles],
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
        $actor = $request->user();
        $actorIsSuperAdmin = (bool) ($actor?->isSuperAdmin());
        $allowedRoles = $actorIsSuperAdmin
            ? 'viewer,operator,useradmin,admin'
            : 'viewer,operator,useradmin';

        $payload = $request->validate([
            'role'     => ['required', 'in:'.$allowedRoles],
            'is_active' => ['required', 'boolean'],
            'name'     => ['nullable', 'string', 'max:255'],
            // Alias: izinkan emoji & simbol Unicode, max 50 karakter (bukan bytes)
            'alias'    => ['nullable', 'string', function ($attribute, $value, $fail) {
                if (mb_strlen((string) $value, 'UTF-8') > 50) {
                    $fail('Alias maksimal 50 karakter.');
                }
            }],
        ]);

        $superAdminEmail = strtolower((string) config('auth.super_admin_email'));
        $isSuperAdmin = $superAdminEmail !== '' && strtolower((string) $user->email) === $superAdminEmail;

        if (! $actorIsSuperAdmin && ($isSuperAdmin || $user->is_superadmin || $user->role === 'admin')) {
            return back()->with('status', 'Akun admin/superadmin hanya bisa dikelola oleh superadmin utama.');
        }

        // Prevent an admin from locking themselves out accidentally.
        if ($actor && $actor->id === $user->id && ! $payload['is_active']) {
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

        // Only persist name/alias if provided (superadmin intent)
        $updateData = array_filter([
            'role'      => $payload['role'],
            'is_active' => $payload['is_active'],
            'name'      => $payload['name'] ?? null,
            'alias'     => array_key_exists('alias', $payload) ? $payload['alias'] : null,
        ], fn ($v) => $v !== null);

        if (array_key_exists('alias', $payload)) {
            $updateData['alias'] = $payload['alias']; // allow explicit null/empty
        }

        $user->update($updateData);

        return back()->with('status', 'Akses pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $actorIsSuperAdmin = (bool) ($actor?->isSuperAdmin());

        $superAdminEmail = strtolower((string) config('auth.super_admin_email'));
        $isTargetSuperAdmin = $superAdminEmail !== '' && strtolower((string) $user->email) === $superAdminEmail;

        if (! $actorIsSuperAdmin && ($isTargetSuperAdmin || $user->is_superadmin || $user->role === 'admin')) {
            return back()->with('status', 'Akun admin/superadmin hanya bisa dihapus oleh superadmin utama.');
        }

        if ($actor && $actor->id === $user->id) {
            return back()->with('status', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang dipakai.');
        }

        $user->delete();

        return back()->with('status', 'Pengguna berhasil dihapus.');
    }

    public function updateRoleFeaturePermission(Request $request): RedirectResponse
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Hanya superadmin yang dapat mengelola permission fitur.');
        }

        $allowedRoles = implode(',', array_keys(FeaturePermission::ROLE_MAP));

        $payload = $request->validate([
            'role' => ['required', 'in:'.$allowedRoles],
            'feature_key' => ['required', 'string', 'max:120'],
            'enabled' => ['required', 'boolean'],
        ]);

        $this->assertFeatureExists($payload['feature_key']);

        $roleId = FeaturePermission::roleIdFor($payload['role']);
        $before = $this->resolveRoleFeatureAccess($payload['role'], $payload['feature_key']);

        FeaturePermission::query()->updateOrCreate(
            [
                'role_id' => $roleId,
                'feature_key' => $payload['feature_key'],
            ],
            [
                'user_id' => null,
                'enabled' => (bool) $payload['enabled'],
            ],
        );

        $this->recordPermissionAudit(
            request: $request,
            scope: 'role',
            action: 'role_permission_update',
            featureKey: $payload['feature_key'],
            role: $payload['role'],
            oldEnabled: $before,
            newEnabled: (bool) $payload['enabled'],
            targetUserId: null,
        );

        return back()->with('status', 'Permission role berhasil diperbarui.');
    }

    public function updateUserFeaturePermission(Request $request): RedirectResponse
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Hanya superadmin yang dapat mengelola permission fitur.');
        }

        $payload = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'feature_key' => ['required', 'string', 'max:120'],
            'enabled' => ['required', 'boolean'],
        ]);

        $this->assertFeatureExists($payload['feature_key']);

        $target = User::query()->findOrFail($payload['user_id']);
        $existing = FeaturePermission::query()
            ->where('user_id', $target->id)
            ->where('feature_key', $payload['feature_key'])
            ->first();

        $before = $existing ? (bool) $existing->enabled : null;

        FeaturePermission::query()->updateOrCreate(
            [
                'user_id' => $target->id,
                'feature_key' => $payload['feature_key'],
            ],
            [
                'role_id' => null,
                'enabled' => (bool) $payload['enabled'],
            ],
        );

        $this->recordPermissionAudit(
            request: $request,
            scope: 'user',
            action: 'user_permission_override_set',
            featureKey: $payload['feature_key'],
            role: $target->role,
            oldEnabled: $before,
            newEnabled: (bool) $payload['enabled'],
            targetUserId: $target->id,
            meta: ['target_email' => $target->email],
        );

        return back()->with('status', 'Override permission user berhasil diperbarui.');
    }

    public function clearUserFeaturePermission(Request $request): RedirectResponse
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Hanya superadmin yang dapat mengelola permission fitur.');
        }

        $payload = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'feature_key' => ['required', 'string', 'max:120'],
        ]);

        $this->assertFeatureExists($payload['feature_key']);

        $target = User::query()->findOrFail($payload['user_id']);

        $existing = FeaturePermission::query()
            ->where('user_id', $payload['user_id'])
            ->where('feature_key', $payload['feature_key'])
            ->first();

        $before = $existing ? (bool) $existing->enabled : null;

        FeaturePermission::query()
            ->where('user_id', $payload['user_id'])
            ->where('feature_key', $payload['feature_key'])
            ->delete();

        $this->recordPermissionAudit(
            request: $request,
            scope: 'user',
            action: 'user_permission_override_cleared',
            featureKey: $payload['feature_key'],
            role: $target->role,
            oldEnabled: $before,
            newEnabled: null,
            targetUserId: $target->id,
            meta: ['target_email' => $target->email],
        );

        return back()->with('status', 'Override permission user berhasil di-reset ke default role.');
    }

    public function storeAllowlist(Request $request): RedirectResponse
    {
        if (! $request->user()?->isSuperAdmin()) {
            return back()->with('status', 'Allowlist Google hanya bisa dikelola superadmin utama.');
        }

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
        if (! $request->user()?->isSuperAdmin()) {
            return back()->with('status', 'Allowlist Google hanya bisa dikelola superadmin utama.');
        }

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
        if (! request()->user()?->isSuperAdmin()) {
            return back()->with('status', 'Allowlist Google hanya bisa dikelola superadmin utama.');
        }

        $entry->delete();

        return back()->with('status', 'Email Google berhasil dihapus dari daftar akses.');
    }

    private function roleFeaturePermissions(): array
    {
        $features = array_values(config('features.features', []));
        $roles = array_keys(FeaturePermission::ROLE_MAP);

        $matrix = [];
        foreach ($features as $feature) {
            $featureKey = (string) ($feature['key'] ?? '');
            if ($featureKey === '') {
                continue;
            }

            foreach ($roles as $role) {
                $matrix[$featureKey][$role] = in_array($role, $feature['default_roles'] ?? [], true);
            }
        }

        FeaturePermission::query()
            ->whereNotNull('role_id')
            ->get(['role_id', 'feature_key', 'enabled'])
            ->each(function (FeaturePermission $entry) use (&$matrix): void {
                $role = array_search((int) $entry->role_id, FeaturePermission::ROLE_MAP, true);
                if (! is_string($role)) {
                    return;
                }

                $matrix[$entry->feature_key][$role] = (bool) $entry->enabled;
            });

        return $matrix;
    }

    private function assertFeatureExists(string $featureKey): void
    {
        $exists = collect(config('features.features', []))
            ->pluck('key')
            ->contains($featureKey);

        abort_unless($exists, 422, 'Feature key tidak terdaftar.');
    }

    private function resolveRoleFeatureAccess(string $role, string $featureKey): bool
    {
        $roleId = FeaturePermission::roleIdFor($role);

        $existing = FeaturePermission::query()
            ->where('role_id', $roleId)
            ->where('feature_key', $featureKey)
            ->first();

        if ($existing) {
            return (bool) $existing->enabled;
        }

        $feature = collect(config('features.features', []))->firstWhere('key', $featureKey);

        return in_array($role, $feature['default_roles'] ?? [], true);
    }

    private function recordPermissionAudit(
        Request $request,
        string $scope,
        string $action,
        string $featureKey,
        ?string $role,
        ?bool $oldEnabled,
        ?bool $newEnabled,
        ?int $targetUserId,
        array $meta = [],
    ): void {
        PermissionAuditLog::query()->create([
            'actor_user_id' => $request->user()?->id,
            'target_user_id' => $targetUserId,
            'scope' => $scope,
            'action' => $action,
            'role' => $role,
            'feature_key' => $featureKey,
            'old_enabled' => $oldEnabled,
            'new_enabled' => $newEnabled,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
