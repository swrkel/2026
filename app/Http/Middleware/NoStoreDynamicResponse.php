<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NoStoreDynamicResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Keep authenticated and dynamic ERP responses out of browser/proxy
        // caches without invoking LiteSpeed ESI/cache middleware.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
