<?php
namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Services\PerformanceCacheService;
use Modules\StockTransferNew\Services\QueryHealthService;
use Modules\StockTransferNew\Services\LargeExportService;
use Modules\StockTransferNew\Services\TenantScopeService;

class PerformanceController extends Controller
{
    public function dashboard(PerformanceCacheService $cache, TenantScopeService $scope)
    {
        $businessId = $scope->businessId();
        $summary = $cache->dashboard($businessId);
        return view('stocktransfernew::performance.dashboard', compact('summary'));
    }

    public function queryHealth(QueryHealthService $health)
    {
        $tables = $health->tableStats();
        $recommendations = $health->indexRecommendations();
        return view('stocktransfernew::performance.query_health', compact('tables', 'recommendations'));
    }

    public function cacheControl(PerformanceCacheService $cache, TenantScopeService $scope)
    {
        $businessId = $scope->businessId();
        $summary = $cache->dashboard($businessId);
        return view('stocktransfernew::performance.cache_control', compact('summary'));
    }

    public function warmCache(PerformanceCacheService $cache, TenantScopeService $scope)
    {
        $cache->warm($scope->businessId());
        return back()->with('status', 'Stock Transfer-New cache warmed successfully.');
    }

    public function clearCache(PerformanceCacheService $cache, TenantScopeService $scope)
    {
        $cache->forget($scope->businessId());
        return back()->with('status', 'Stock Transfer-New cache cleared successfully.');
    }

    public function exportQueue(LargeExportService $exports, TenantScopeService $scope)
    {
        $items = $exports->recent($scope->businessId());
        return view('stocktransfernew::performance.export_queue', compact('items'));
    }

    public function queueExport(Request $request, LargeExportService $exports, TenantScopeService $scope)
    {
        $request->validate(['report_type' => 'required|string|max:100']);
        $exports->queue($request->input('report_type'), $request->except('_token'), $scope->businessId());
        return back()->with('status', 'Large export queued successfully.');
    }
}
