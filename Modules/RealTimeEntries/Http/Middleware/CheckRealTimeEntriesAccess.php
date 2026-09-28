<?php

namespace Modules\RealTimeEntries\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Superadmin\Entities\Subscription;

class CheckRealTimeEntriesAccess
{
    /**
     * Protect Real Time Entries from both disabled subscriptions and direct URL access.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();
        $business_id = $request->session()->get('user.business_id')
            ?? $request->session()->get('business.id')
            ?? ($user->business_id ?? null);

        if (empty($business_id)) {
            abort(403, 'Business not resolved.');
        }

        $subscription = Subscription::current_subscription($business_id);
        $package_details = [];

        if (! empty($subscription) && ! empty($subscription->package_details)) {
            $package_details = is_array($subscription->package_details)
                ? $subscription->package_details
                : (array) $subscription->package_details;
        }

        $module_enabled = ! empty($package_details['real_time_entries'])
            || ! empty($package_details['real_time_entries_module'])
            || ! empty($package_details['enable_real_time_entries'])
            || ! empty($package_details['enable_real_time_entries_module']);

        if (! $module_enabled) {
            abort(403, 'Real Time Entries module is not enabled for this business.');
        }

        $is_admin = method_exists($user, 'hasRole') && $user->hasRole('Admin#' . $business_id);

        if ($user->can('superadmin') || $is_admin) {
            return $next($request);
        }

        $allowed_permissions = [
            'real_time_entries.access',
            'real_time_entries.view',
            'real_time_entries.payments',
            'real_time_entries.other_sales',
            'real_time_entries.reports',
            'real_time_entries.settings',
            'real_time_entries',
            'realtimeentries.access',
        ];

        foreach ($allowed_permissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to access Real Time Entries.');
    }
}
