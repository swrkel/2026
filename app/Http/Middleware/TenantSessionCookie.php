<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantSessionCookie
{
    public function handle(Request $request, Closure $next)
    {
        $base = (string) env('SESSION_COOKIE', Str::slug((string) env('APP_NAME', 'laravel'), '_') . '_session');
        $host = Str::slug(strtolower($request->getHost()), '_');

        // Each tenant host receives an independent cookie name. This prevents a
        // central-domain session from masking or reviving a tenant session.
        config([
            'session.cookie' => $base . '_' . $host,
            'session.domain' => null,
        ]);

        return $next($request);
    }
}
