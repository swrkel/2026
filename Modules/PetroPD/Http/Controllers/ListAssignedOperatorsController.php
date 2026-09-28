<?php

namespace Modules\PetroPD\Http\Controllers;

use App\Business;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PetroPD\Services\Reports\AssignedOperatorsReportService;

class ListAssignedOperatorsController extends Controller
{
    public function index(Request $request, AssignedOperatorsReportService $service)
    {
        $businessId = $this->businessId($request);
        $this->authorizePage($businessId);

        if ($request->ajax()) {
            return $service->dataTable($request, $businessId);
        }

        $filters = $service->filterOptions($businessId);
        $business = Business::find($businessId);
        $financialYearStartMonth = max(1, min(12, (int) ($business->fy_start_month ?? 1)));

        return view('petropd::report.list_assigned_operators', compact(
            'filters',
            'financialYearStartMonth'
        ));
    }

    public function settlementOptions(Request $request, AssignedOperatorsReportService $service)
    {
        $businessId = $this->businessId($request);
        $this->authorizePage($businessId);

        return response()->json([
            'success' => true,
            'settlements' => $service->settlementOptions($request, $businessId),
        ]);
    }

    private function businessId(Request $request): int
    {
        $businessId = (int) ($request->session()->get('business.id')
            ?: $request->session()->get('user.business_id')
            ?: optional(auth()->user())->business_id);

        abort_if($businessId <= 0, 403, 'Business context is not available.');

        return $businessId;
    }

    private function authorizePage(int $businessId): void
    {
        $user = auth()->user();

        abort_unless(
            $user && (
                $user->can('superadmin')
                || $user->hasRole('Admin#' . $businessId)
                || $user->can('petro_pd.view_operators')
            ),
            403,
            'Unauthorized Access'
        );
    }
}
