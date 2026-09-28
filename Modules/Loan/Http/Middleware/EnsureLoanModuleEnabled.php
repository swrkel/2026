<?php

namespace Modules\Loan\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Superadmin\Entities\Subscription;

class EnsureLoanModuleEnabled
{
    /**
     * Block Loan module direct URL access when the Loan module is disabled
     * in Super Admin subscription/package settings.
     *
     * This middleware is intentionally placed inside Modules/Loan so the Loan
     * module remains standalone and does not depend on main-system middleware.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id')
            ?? $request->session()->get('business.id')
            ?? optional(auth()->user())->business_id;

        if (empty($business_id)) {
            Log::warning('Loan module access blocked: business_id missing', [
                'user_id' => auth()->id(),
                'path' => $request->path(),
            ]);
            abort(403, 'Loan module is not enabled for this business.');
        }

        $enabled = true; // legacy-safe default if old packages do not yet contain loan_module flag

        try {
            $subscription = Subscription::current_subscription($business_id);
            $package_details = ! empty($subscription) ? (array) $subscription->package_details : [];

            if (array_key_exists('loan_module', $package_details)) {
                $enabled = ! empty($package_details['loan_module']);
            }
        } catch (\Throwable $e) {
            Log::warning('Loan module subscription check failed', [
                'business_id' => $business_id,
                'user_id' => auth()->id(),
                'path' => $request->path(),
                'message' => $e->getMessage(),
            ]);
        }

        if (! $enabled) {
            abort(403, 'Loan module is not enabled for this business.');
        }

        return $next($request);
    }
}
