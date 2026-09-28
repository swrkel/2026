<?php

namespace Modules\LeadsNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureLeadsNewEnabled
{
    public function handle(Request $request, Closure $next)
    {
        // Final implementation should read the business package flag used by Super Admin / All Businesses / Manage.
        // Kept isolated in LeadsNew so sidebar/route gating logic is easy to maintain.
        return $next($request);
    }
}
