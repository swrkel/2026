<?php

namespace App\Http\Middleware;

use App\Business;
use App\Services\GlobalPerformanceCache;
use App\Utils\SidebarPermissionUtil;
use Closure;
use Modules\Superadmin\Entities\Subscription;

class CheckSubscribed
{
    public function handle($request, Closure $next, $module)
    {
        if (SidebarPermissionUtil::hasSuperAdminBypass()) {
            return $next($request);
        }

        /*
         * The session may not exist on this request.
         *
         * Reported on /deposits-module/account:
         *     RuntimeException: Session store not set on request.
         *     CheckSubscribed.php line 19 -> $request->session()
         *
         * $request->session() THROWS when no session store is bound, and that
         * only happens when this middleware runs BEFORE Laravel's StartSession -
         * in other words when the route is registered outside the `web`
         * middleware group. The page then dies with a 500 instead of behaving
         * like any other unauthenticated request.
         *
         * A request with no session cannot be authenticated, so it cannot have a
         * business or a subscription either. Sending it to login is the same
         * outcome the check below already produces for a missing business id -
         * it just gets there without an exception.
         *
         * This makes the middleware safe wherever it is used, but it does not
         * make the route registration correct: the proper fix is for that route
         * group to include the `web` middleware, so sessions, CSRF and cookies
         * all work as they do everywhere else. Without that, the page will
         * redirect to login rather than open.
         */
        if (! $request->hasSession()) {
            return redirect('/login');
        }

        $businessId = (int) $request->session()->get('user.business_id');
        if ($businessId <= 0) {
            return redirect('/logout');
        }

        $businessExists = GlobalPerformanceCache::remember(
            'business_exists',
            [$businessId],
            120,
            static fn () => Business::query()->whereKey($businessId)->exists()
        );

        if (! $businessExists) {
            return redirect('/logout');
        }

        $packageDetails = GlobalPerformanceCache::remember(
            'subscription_package_details',
            [$businessId],
            60,
            static function () use ($businessId) {
                $subscription = Subscription::current_subscription($businessId);
                return $subscription ? (array) $subscription->package_details : [];
            }
        );

        if (! empty($packageDetails)
            && empty($packageDetails[$module])
            && ! empty($packageDetails['ns_' . $module])) {
            return redirect('home/not-subscribed');
        }

        return $next($request);
    }
}
