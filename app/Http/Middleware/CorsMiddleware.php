<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

/**
 * CORS (Cross-Origin Resource Sharing) Middleware
 * 
 * Configures allowed origins, methods, and headers for API access.
 * Prevents unauthorized cross-origin requests.
 */
class CorsMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        $allowedOrigins = config('security.cors.allowed_origins', [
            'http://localhost:3000',
            'http://localhost:5173', // Vite dev server
            'https://lawangsewu.app',
        ]);

        $origin = $request->header('Origin');
        
        // Check if origin is allowed
        $isAllowed = in_array($origin, $allowedOrigins) || 
                     $this->matchesWildcard($origin, $allowedOrigins);

        if ($isAllowed) {
            $response = $next($request);
            
            // Add CORS headers
            return $response
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Trace-ID')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '3600');
        }

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', config('security.cors.default_origin', '*'))
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                ->header('Access-Control-Max-Age', '3600');
        }

        return response('Forbidden', 403);
    }

    /**
     * Check if origin matches wildcard pattern
     */
    private function matchesWildcard($origin, $patterns)
    {
        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                $pattern = preg_quote($pattern, '/');
                $pattern = str_replace('\*', '.*', $pattern);
                if (preg_match("/^{$pattern}$/", $origin)) {
                    return true;
                }
            }
        }
        return false;
    }
}
