<?php

namespace App\Http\Middleware;

use App\Models\FeaturePermission;
use Closure;
use Illuminate\Http\Request;

class Permission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Unauthorized'], 401)
                : redirect()->route('login');
        }

        $hasPermission = FeaturePermission::hasAccess($user, $permission);

        if (!$hasPermission) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Forbidden'], 403)
                : abort(403, 'Anda tidak memiliki akses ke fitur ini.');
        }

        return $next($request);
    }
}
