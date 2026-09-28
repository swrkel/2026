<?php
namespace Modules\AirlineTicketingNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnforceFeaturePermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        abort_unless(auth()->check(),403);
        abort_unless(auth()->user()->can($permission),403);

        return $next($request);
    }
}
