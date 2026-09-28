<?php

namespace Modules\PetroPD\Http\Controllers;

use App\Business;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PetroPD\Services\Reports\AdjustedAmountsReportService;

class AdjustedAmountsReportController extends Controller
{
    public function index(Request $request, AdjustedAmountsReportService $service)
    {
        $businessId = $this->businessId($request);
        $this->authorizeReport($businessId);

        if ($request->ajax()) {
            return $service->dataTable($request, $businessId);
        }

        $filters = $service->filterOptions($businessId);
        $business = Business::find($businessId);
        $currencyPrecision = max(0, min(4, (int) ($business->currency_precision ?? 2)));
        $financialYearStartMonth = max(1, min(12, (int) ($business->fy_start_month ?? 1)));

        return view('petropd::report.adjusted_amounts', compact(
            'filters',
            'currencyPrecision',
            'financialYearStartMonth'
        ));
    }

    private function businessId(Request $request): int
    {
        $businessId = (int) ($request->session()->get('business.id')
            ?: $request->session()->get('user.business_id')
            ?: optional(auth()->user())->business_id);

        abort_if($businessId <= 0, 403, 'Business context is not available.');

        return $businessId;
    }

    private function authorizeReport(int $businessId): void
    {
        $user = auth()->user();

        abort_unless(
            $user && (
                $user->can('superadmin')
                || $user->hasRole('Admin#' . $businessId)
                || $user->can('petro_pd.view_adjusted_amounts_report')
                || $user->can('petro_pd.view_report')
            ),
            403,
            'Unauthorized Access'
        );
    }
}
