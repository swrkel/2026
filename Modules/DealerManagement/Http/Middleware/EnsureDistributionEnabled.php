<?php
namespace Modules\DealerManagement\Http\Middleware;

use Closure;
use Modules\DealerManagement\Services\DistributionAvailabilityService;

class EnsureDistributionEnabled
{
    public function handle($request, Closure $next)
    {
        $businessId = (int) (
            $request->input('business_id')
            ?: session('dealer_management_business_id')
            ?: session('business.id')
            ?: session('business_id')
            ?: session('user.business_id')
        );

        $service = app(DistributionAvailabilityService::class);

        // For an ERP-side request we know the current business and can enforce
        // it directly. For the public dealer login page, allow the login form to
        // render and validate the chosen business on POST instead of returning
        // an unexplained 404 before the dealer can log in.
        if ($businessId && !$service->enabledForBusiness($businessId)) {
            abort(404);
        }

        return $next($request);
    }
}
