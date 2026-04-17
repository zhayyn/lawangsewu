<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalSsoController extends Controller
{
    public function consume(Request $request): RedirectResponse
    {
        $secret = (string) config('lawangsewu.sso_shared_secret', '');
        if ($secret === '') {
            abort(503, 'SSO shared secret belum dikonfigurasi.');
        }

        $payload = (string) $request->query('payload', '');
        $sig = (string) $request->query('sig', '');
        if ($payload === '' || $sig === '') {
            abort(401, 'Payload SSO tidak valid.');
        }

        $expectedSig = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expectedSig, $sig)) {
            abort(401, 'Signature SSO tidak cocok.');
        }

        $base64 = strtr($payload, '-_', '+/');
        $padding = strlen($base64) % 4;
        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        $decodedRaw = base64_decode($base64, true);
        $decoded = json_decode((string) $decodedRaw, true);
        if (!is_array($decoded)) {
            abort(401, 'Payload SSO gagal diparse.');
        }

        $timestamp = (int) ($decoded['ts'] ?? 0);
        $ttl = (int) config('lawangsewu.sso_ttl_seconds', 300);
        if ($timestamp <= 0 || abs(time() - $timestamp) > $ttl) {
            abort(401, 'Payload SSO kedaluwarsa.');
        }

        $externalUid = (string) ($decoded['uid'] ?? '');
        $username = trim((string) ($decoded['username'] ?? ''));
        $fullName = trim((string) ($decoded['full_name'] ?? $username));
        $role = trim(strtolower((string) ($decoded['role'] ?? config('lawangsewu.default_user_role', User::ROLE_VIEWER))));

        if ($externalUid === '' || $username === '') {
            abort(401, 'Identitas SSO tidak lengkap.');
        }

        $allowedRoles = [User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_VIEWER];
        if (!in_array($role, $allowedRoles, true)) {
            $role = (string) config('lawangsewu.default_user_role', User::ROLE_VIEWER);
        }

        $email = $username . '@portal.lawangsewu.local';

        $user = User::query()
            ->where('external_uid', $externalUid)
            ->orWhere(function ($query) use ($username, $email): void {
                $query->where('auth_source', 'portal-sso')
                    ->where(function ($q) use ($username, $email): void {
                        $q->where('username', $username)->orWhere('email', $email);
                    });
            })
            ->first();

        if (!$user instanceof User) {
            $user = new User();
        }

        $user->fill([
            'external_uid' => $externalUid,
            'username' => $username,
            'name' => $fullName !== '' ? $fullName : $username,
            'email' => $email,
            'role' => $role,
            'auth_source' => 'portal-sso',
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login_at' => now(),
        ]);

        if (!$user->exists) {
            $user->password = bin2hex(random_bytes(20));
        }

        $user->save();

        if (!$user->is_active) {
            abort(403, 'Akun non-aktif. Hubungi admin Lawangsewu.');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $target = '/' . ltrim((string) ($decoded['target'] ?? 'dashboard'), '/');
        if (!str_starts_with($target, '/')) {
            $target = '/dashboard';
        }

        return redirect($target);
    }
}
