<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Finance\Entities\FinanceLiquidityRisk;
use Modules\Finance\Services\FinanceLiquidityRiskService;

class FinanceLiquidityRiskController extends Controller
{
    public function index()
    {
        $business_id = session('business.id');

        $risks = FinanceLiquidityRisk::where(
            'business_id',
            $business_id
        )
        ->with(['location', 'createdBy'])
        ->latest()
        ->paginate(20);

        return view(
            'finance::liquidity_risk.index',
            compact('risks')
        );
    }

    public function run()
    {
        FinanceLiquidityRiskService::monitor();

        return redirect()
            ->route('finance.liquidity_risk.index')
            ->with('status', [
                'success' => 1,
                'msg' => 'Liquidity risk monitoring completed successfully'
            ]);
    }
}