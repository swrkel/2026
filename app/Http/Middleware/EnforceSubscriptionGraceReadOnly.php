<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Modules\Superadmin\Entities\Subscription;

class EnforceSubscriptionGraceReadOnly
{
    /**
     * During the expired-subscription grace window, pages remain visible but
     * write actions are blocked until the account is renewed.
     */
    public function handle($request, Closure $next)
    {
        if (
            ! Auth::check()
            || Auth::user()->can('superadmin')
            || $request->isMethodSafe()
            || $this->isAllowedWriteRoute($request)
        ) {
            return $next($request);
        }

        $business_id = session()->get('user.business_id')
            ?? session()->get('business.id')
            ?? Auth::user()->business_id;

        if (! Schema::connection('system')->hasTable('subscriptions')) {
            return $next($request);
        }

        if (! Subscription::is_in_expired_grace_period($business_id)) {
            return $next($request);
        }

        $details = Subscription::expired_grace_period_details($business_id);
        $message = $details['message'] ?? 'Subscription has expired, please renew your account to enter details.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => 0,
                'msg' => $message,
            ], 403);
        }

        return redirect()->back()->with('status', [
            'success' => 0,
            'msg' => $message,
        ]);
    }

    private function isAllowedWriteRoute($request): bool
    {
        return $request->is(
            'logout',
            'subscription',
            'subscription/*',
            'pay-online',
            'pay-online/*',
            'superadmin/family-subscription/pay',
            'superadmin/family-subscription/confirm',
            'family-subscription/notify-payhere',
            'subscription/payhere/*'
        );
    }
}
