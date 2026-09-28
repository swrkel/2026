<?php

namespace Modules\StockTakingNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTakingNew\Entities\StockTakeSession;
use Modules\StockTakingNew\Services\MasterDataBridgeService;
use Modules\StockTakingNew\Services\ReportService;
use Modules\StockTakingNew\Services\TenantScopeService;

class ReportController extends Controller
{
    private function filters(Request $request, TenantScopeService $scope): array
    {
        $filters = $request->all();
        $allowed = $scope->permittedLocationIdsForFilters();
        if ($allowed !== null) {
            $filters['_permitted_location_ids'] = $allowed;
        }
        return $filters;
    }

    private function context(Request $request, TenantScopeService $scope, MasterDataBridgeService $masters): array
    {
        $businessId = $scope->businessId($request);
        abort_unless($businessId, 403);

        $sessionQuery = StockTakeSession::where('business_id', $businessId);
        $scope->applyLocationScope($sessionQuery);

        return [
            $businessId,
            $masters->locations($businessId),
            $masters->stores($businessId, $request->integer('location_id') ?: null),
            $sessionQuery->latest('count_date')->pluck('stock_take_no', 'id'),
        ];
    }

    public function index(Request $request, TenantScopeService $scope, MasterDataBridgeService $masters)
    {
        [, $locations, $stores, $sessions] = $this->context($request, $scope, $masters);
        return view('stocktakingnew::reports.index', compact('locations', 'stores', 'sessions'));
    }

    public function variance(Request $request, TenantScopeService $scope, MasterDataBridgeService $masters, ReportService $reports)
    {
        [$businessId, $locations, $stores, $sessions] = $this->context($request, $scope, $masters);
        $rows = $reports->varianceQuery($businessId, $this->filters($request, $scope))
            ->orderByDesc('s.count_date')->orderBy('product_name')->paginate(100)->withQueryString();
        return view('stocktakingnew::reports.variance', compact('rows', 'locations', 'stores', 'sessions'));
    }

    public function progress(Request $request, TenantScopeService $scope, MasterDataBridgeService $masters, ReportService $reports)
    {
        [$businessId, $locations, $stores, $sessions] = $this->context($request, $scope, $masters);
        $rows = $reports->sessionQuery($businessId, $this->filters($request, $scope))
            ->latest('count_date')->paginate(50)->withQueryString();
        return view('stocktakingnew::reports.progress', compact('rows', 'locations', 'stores', 'sessions'));
    }

    public function accuracy(Request $request, TenantScopeService $scope, MasterDataBridgeService $masters, ReportService $reports)
    {
        [$businessId, $locations, $stores, $sessions] = $this->context($request, $scope, $masters);
        $rows = $reports->sessionQuery($businessId, $this->filters($request, $scope))
            ->whereIn('status', ['submitted', 'approved', 'posted'])
            ->latest('count_date')->paginate(50)->withQueryString();
        return view('stocktakingnew::reports.accuracy', compact('rows', 'locations', 'stores', 'sessions'));
    }

    public function audit(Request $request, TenantScopeService $scope, MasterDataBridgeService $masters, ReportService $reports)
    {
        [$businessId, $locations, $stores, $sessions] = $this->context($request, $scope, $masters);
        $rows = $reports->auditQuery($businessId, $this->filters($request, $scope))
            ->latest('id')->paginate(100)->withQueryString();
        return view('stocktakingnew::reports.audit', compact('rows', 'locations', 'stores', 'sessions'));
    }

    public function exportVariance(Request $request, TenantScopeService $scope, ReportService $reports)
    {
        $businessId = $scope->businessId($request);
        abort_unless($businessId, 403);
        $rows = $reports->varianceQuery($businessId, $this->filters($request, $scope))->orderBy('product_name')->cursor();

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Stock Take No', 'Date', 'SKU', 'Product', 'System Qty', 'Counted Qty', 'Variance Qty', 'Unit Cost', 'Variance Value', 'Status']);
            foreach ($rows as $row) {
                fputcsv($output, [
                    $row->stock_take_no, $row->count_date, $row->sku, $row->product_name,
                    $row->system_qty, $row->final_count_qty, $row->variance_qty,
                    $row->unit_cost, $row->variance_value, $row->session_status,
                ]);
            }
            fclose($output);
        }, 'stock-taking-variance-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
