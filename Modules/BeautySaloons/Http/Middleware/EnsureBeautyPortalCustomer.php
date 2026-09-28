<?php

namespace Modules\BeautySaloons\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\BeautySaloons\Services\Portal\PortalCustomerSessionService;

class EnsureBeautyPortalCustomer
{
    public function handle(Request $request, Closure $next)
    {
        if (! session()->has(PortalCustomerSessionService::SESSION_KEY)) {
            return redirect()->route('beautysaloons.portal.login');
        }

        return $next($request);
    }
}
