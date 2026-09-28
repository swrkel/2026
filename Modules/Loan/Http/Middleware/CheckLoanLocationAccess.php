<?php

namespace Modules\Loan\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Superadmin\Entities\ModulePermissionLocation;
use Modules\Loan\Entities\Loan;

class CheckLoanLocationAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if ($user && $user->can('superadmin')) {
            return $next($request);
        }

        $business_id = $request->session()->get('user.business_id');
        if (empty($business_id)) {
            return $next($request);
        }

        $allowed_locations = ModulePermissionLocation::getModulePermissionLocations($business_id, 'loan_module');
        $allowed_location_ids = [];
        if (!empty($allowed_locations) && !empty($allowed_locations->locations)) {
            $allowed_location_ids = array_keys(array_filter($allowed_locations->locations, function($val) {
                return $val == 1;
            }));
        }

        $loan_id = $request->route('id') ?? $request->route('loan_id');
        if ($loan_id) {
            $loan = Loan::find($loan_id);
            if ($loan && !in_array($loan->location_id, $allowed_location_ids)) {
                abort(403, 'Unauthorized location for this loan.');
            }
        }

        if ($request->has('location_id')) {
            $req_location_id = $request->input('location_id');
            if ($req_location_id && !in_array($req_location_id, $allowed_location_ids)) {
                abort(403, 'Unauthorized location for this loan.');
            }
        }

        $current_location_id = $request->session()->get('user.current_location');
        if ($current_location_id && !in_array($current_location_id, $allowed_location_ids)) {
            abort(403, 'Unauthorized access. Loan Module is not enabled for your current location.');
        }

        return $next($request);
    }
}
