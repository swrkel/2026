<?php

namespace Modules\RestaurantNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRestaurantNewBusinessScope
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            abort(401);
        }

        if (! session()->has('business.id')) {
            abort(403, __('restaurantnew::lang.business_scope_missing'));
        }

        return $next($request);
    }
}
