<?php

namespace Modules\Customers\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CustomerPortalMiddleware
{
    /**
     * Distribution Dealer / Customer Portal middleware.
     *
     * This intentionally does not use ERP auth. It checks only the dedicated
     * Customers module portal session so dealer pages never load ERP sidebars
     * or ERP permission checks.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('distribution-dealer/login') || $request->is('distribution-dealer/authenticate')) {
            return $next($request);
        }

        if (! $request->session()->has('customer_portal.contact_id')) {
            return redirect('/distribution-dealer/login')
                ->with('status', [
                    'success' => 0,
                    'msg' => 'Please login to continue.',
                    'background' => 'alert-danger',
                ]);
        }

        return $next($request);
    }
}
