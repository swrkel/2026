<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Entities\FinanceKpiSnapshot;
use Modules\Finance\Services\FinanceKpiService;

class FinanceKpiController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session('business.id');

        $locations = BusinessLocation::where(
            'business_id',
            $business_id
        )->pluck('name', 'id');

        $query = FinanceKpiSnapshot::where(
            'business_id',
            $business_id
        )
        ->with(['location', 'createdBy'])
        ->latest();

        if (!empty($request->location_id) && $request->location_id != 'all') {
            $query->where('location_id', $request->location_id);
        }

        $snapshots = $query->paginate(20);

        return view('finance::kpi.index')
            ->with(compact(
                'snapshots',
                'locations'
            ));
    }

    public function generate(Request $request)
    {
        $business_id = session('business.id');

        $location_id = null;

        if (!empty($request->location_id) && $request->location_id != 'all') {
            $location_id = $request->location_id;
        }

        FinanceKpiService::generateSnapshot(
            $business_id,
            $location_id
        );

        return redirect()
            ->route('finance.kpi.index')
            ->with('status', [
                'success' => 1,
                'msg' => 'Finance KPI snapshot generated successfully'
            ]);
    }
}