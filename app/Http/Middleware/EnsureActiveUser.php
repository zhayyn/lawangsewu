<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akun belum aktif. Hubungi admin untuk aktivasi.',
                ], 403);
            }

            auth()->guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('login')
                ->with('status', 'Akun belum aktif. Hubungi admin untuk aktivasi.');
        }

        return $next($request);
    }
}
