<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if ($roles === []) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Anda tidak memiliki izin untuk mengakses resource ini.',
                ], 403);
            }

            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
