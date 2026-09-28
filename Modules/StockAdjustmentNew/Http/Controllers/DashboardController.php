<?php

namespace Modules\StockAdjustmentNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockAdjustmentNew\Entities\StockAdjustment;
use Modules\StockAdjustmentNew\Services\StockAdjustmentSettingsService;
use Modules\StockAdjustmentNew\Services\TenantScopeService;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        TenantScopeService $scope,
        StockAdjustmentSettingsService $settingsService
    ) {
        $businessId = $scope->businessId($request);
        $settings = $settingsService->values($businessId);
        $query = StockAdjustment::query()
            ->when($businessId, fn ($builder) => $builder->where('business_id', $businessId));

        $stats = [
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'submitted' => (clone $query)->where('status', 'submitted')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'posted' => (clone $query)->where('status', 'posted')->count(),
            'total_cost' => (clone $query)->sum('total_cost_amount'),
        ];
        $latest = (clone $query)->latest()->limit(10)->get();

        return view('stockadjustmentnew::dashboard.index', compact('stats', 'latest', 'settings'));
    }
}
