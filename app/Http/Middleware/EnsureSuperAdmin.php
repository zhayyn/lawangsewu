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

        if (! method_exists($user, 'isSuperAdmin') || ! $user->isSuperAdmin()) {
            abort(403, 'Akses khusus superadmin.');
        }

        return $next($request);
    }
}
