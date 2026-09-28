<?php

namespace Modules\AirlineTicketingNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAirlineTicketingNewAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(auth()->check(), 403);

        $user = auth()->user();
        $allowed = method_exists($user, 'can')
            ? ($user->can('airline_ticketing_new.access') || $user->can('superadmin'))
            : false;

        abort_unless($allowed, 403, trans('airlineticketingnew::messages.unauthorised'));

        return $next($request);
    }
}
