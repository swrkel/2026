<?php

namespace Modules\AirlineTicketingNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAirlineTicketingBusinessScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $businessId = (int) session('business.id');
        abort_if($businessId <= 0, 403, trans('airlineticketingnew::messages.business_required'));

        $request->attributes->set('atn_business_id', $businessId);
        $request->attributes->set('atn_location_id', $request->integer('business_location_id') ?: null);
        $request->attributes->set('atn_store_id', $request->integer('store_id') ?: null);

        return $next($request);
    }
}
