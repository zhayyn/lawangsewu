<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\FeaturePermission;

class CheckFeatureAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $featureKey
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!FeaturePermission::hasAccess($user, $featureKey)) {
            abort(403, 'Akses ditolak: Fitur ini tidak diaktifkan untuk akun Anda. Silakan hubungi Administrator untuk meminta akses.');
        }

        return $next($request);
    }
}
