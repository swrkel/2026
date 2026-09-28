<?php

namespace Modules\SW\Http\Middleware;

use Closure;
use Modules\SW\Services\PaymentTabPermissionService;

class EnsurePaymentTabEnabled
{
    public function handle($request, Closure $next, string $tab)
    {
        $businessId = (int) (session('business.id') ?: session('user.business_id') ?: 0);

        abort_unless(
            app(PaymentTabPermissionService::class)->enabled($tab, $businessId),
            403,
            'This SW Payments tab is disabled in Manage New for this business.'
        );

        return $next($request);
    }
}
