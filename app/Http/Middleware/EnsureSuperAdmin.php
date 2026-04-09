<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'Akses khusus superadmin.');
        }

        // Check new is_superadmin flag first
        if ($user->is_superadmin) {
            return $next($request);
        }

        // Fallback to email check for backward compatibility
        $superAdminEmail = strtolower((string) config('auth.super_admin_email', 'dbprakom@gmail.com'));
        if (strtolower((string) $user->email) !== $superAdminEmail) {
            abort(403, 'Akses khusus superadmin.');
        }

        return $next($request);
    }
}
