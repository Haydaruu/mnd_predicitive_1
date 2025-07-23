<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        $origin = $request->headers->get('Origin');

        $allowedOrigins = [
            'http://localhost:8000',
            'http://127.0.0.1:8000',
            'http://localhost:5173',
            'http://127.0.0.1:5173',
        ];
        
        // For same-origin requests, don't add CORS headers
        if (!$origin || $origin === $request->getSchemeAndHttpHost()) {
            return $next($request);
        }
        
        if ($request->getMethod() === "OPTIONS") {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', in_array($origin, $allowedOrigins) ? $origin : 'http://localhost:8000')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-CSRF-TOKEN, Authorization, X-Requested-With, X-XSRF-TOKEN')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }

        return $next($request)
            ->header('Access-Control-Allow-Origin', in_array($origin, $allowedOrigins) ? $origin : 'http://localhost:8000')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-CSRF-TOKEN, Authorization, X-Requested-With, X-XSRF-TOKEN')
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Expose-Headers', 'X-CSRF-TOKEN, X-XSRF-TOKEN');
    }
}