<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Permission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $hasPermission = method_exists($user, 'hasPermission')
            ? $user->hasPermission($permission)
            : false;

        if (!$hasPermission) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
