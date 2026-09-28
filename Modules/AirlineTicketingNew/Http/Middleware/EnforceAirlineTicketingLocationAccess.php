<?php

namespace Modules\AirlineTicketingNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnforceAirlineTicketingLocationAccess
{
    public function handle(Request $request, Closure $next)
    {
        $locationId = $request->integer('business_location_id');

        if ($locationId && method_exists(auth()->user(), 'permitted_locations')) {
            $allowed = collect(auth()->user()->permitted_locations())->contains((string) $locationId);
            abort_unless($allowed, 403);
        }

        return $next($request);
    }
}
