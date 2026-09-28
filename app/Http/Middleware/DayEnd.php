<?php

namespace App\Http\Middleware;

use App\Business;
use App\Services\GlobalPerformanceCache;
use Closure;
use Illuminate\Support\Facades\Auth;

class DayEnd
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('customer')->check()) {
            return $next($request);
        }

        $businessId = (int) $request->session()->get('user.business_id');
        if ($businessId <= 0) {
            return redirect('/logout');
        }

        // The current middleware only verifies that the business still exists;
        // day-end itself intentionally does not block the request. Cache this
        // existence check so ordinary page refreshes do not query business again.
        $sessionBusinessId = (int) data_get($request->session()->get('business'), 'id');
        $businessExists = $sessionBusinessId === $businessId
            ? true
            : (bool) GlobalPerformanceCache::remember(
                'business_exists',
                [$businessId],
                120,
                static fn () => Business::query()->whereKey($businessId)->exists()
            );

        if (! $businessExists) {
            return redirect('/logout');
        }

        // Existing behaviour intentionally permits requests even after day end.
        return $next($request);
    }
}
