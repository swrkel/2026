<?php
namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Entities\FinanceRiskAlert;

class FinanceRiskController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where(
            'business_id',
            $business_id
        )->pluck('name', 'location_id');

        $query = FinanceRiskAlert::where(
            'business_id',
            $business_id
        )
        ->with([
            'location',
            'createdBy',
            'reviewedBy'
        ])
        ->latest();

        if (
            !empty($request->location_id)
            && $request->location_id != 'all'
        ) {

            $query->where(
                'location_id',
                $request->location_id
            );
        }

        if (!empty($request->risk_level)) {

            $query->where(
                'risk_level',
                $request->risk_level
            );
        }

        if (!empty($request->status)) {

            $query->where(
                'status',
                $request->status
            );
        }

        if (!empty($request->module)) {

            $query->where(
                'module',
                'like',
                '%' . $request->module . '%'
            );
        }

        $risk_alerts = $query->paginate(25);

        $total_open_alerts =
            FinanceRiskAlert::where(
                'business_id',
                $business_id
            )
            ->where('status', 'open')
            ->count();

        $critical_alerts =
            FinanceRiskAlert::where(
                'business_id',
                $business_id
            )
            ->where('risk_level', 'critical')
            ->count();

        return view('finance::risk.index')
            ->with(compact(
                'risk_alerts',
                'locations',
                'total_open_alerts',
                'critical_alerts'
            ));
    }
}