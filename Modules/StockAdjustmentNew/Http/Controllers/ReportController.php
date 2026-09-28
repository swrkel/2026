<?php

namespace Modules\StockAdjustmentNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockAdjustmentNew\Entities\StockAdjustment;
use Modules\StockAdjustmentNew\Services\TenantScopeService;

class ReportController extends Controller
{
    public function register(Request $request, TenantScopeService $scope)
    {
        $businessId = $scope->businessId($request);
        $records = StockAdjustment::with('lines')
            ->when($businessId, fn ($query) => $query->where('business_id', $businessId))
            ->latest('adjustment_date')
            ->latest('id')
            ->paginate(50);

        return view('stockadjustmentnew::adjustments.index', [
            'adjustments' => $records,
            'reportMode' => true,
        ]);
    }

    public function variance(Request $request, TenantScopeService $scope)
    {
        $businessId = $scope->businessId($request);
        $records = StockAdjustment::with('lines')
            ->when($businessId, fn ($query) => $query->where('business_id', $businessId))
            ->whereIn('status', ['approved', 'posted'])
            ->latest('adjustment_date')
            ->latest('id')
            ->paginate(50);

        return view('stockadjustmentnew::adjustments.index', [
            'adjustments' => $records,
            'reportMode' => true,
            'varianceMode' => true,
        ]);
    }
}
