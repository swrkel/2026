<?php

namespace Modules\StockTransferNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Services\ProductionHardeningService;

class ProductionHardeningController extends Controller
{
    protected ProductionHardeningService $service;

    public function __construct(ProductionHardeningService $service)
    {
        $this->service = $service;
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->filled('location_id') ? (int) $request->location_id : null;
        $storeId = $request->filled('store_id') ? (int) $request->store_id : null;

        $summary = $this->service->summary($businessId, $locationId, $storeId);
        $checks = $this->service->checks($businessId, $locationId, $storeId);

        return view('stocktransfernew::production_hardening.index', compact('summary', 'checks', 'locationId', 'storeId'));
    }

    public function run(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->filled('location_id') ? (int) $request->location_id : null;
        $storeId = $request->filled('store_id') ? (int) $request->store_id : null;

        $run = $this->service->runChecks($businessId, $locationId, $storeId, $request->input('notes'));

        return redirect()
            ->route('stock-transfer-new.production-hardening.index')
            ->with('status', 'Production hardening run completed: ' . $run->run_no);
    }

    public function export(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $checks = $this->service->checks($businessId, null, null);

        $filename = 'stock_transfer_new_production_hardening_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($checks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Code', 'Area', 'Title', 'Severity', 'Status', 'Actual Result', 'Recommendation', 'Checked At']);
            foreach ($checks as $check) {
                fputcsv($handle, [
                    $check->check_code,
                    $check->check_area,
                    $check->check_title,
                    $check->severity,
                    $check->status,
                    $check->actual_result,
                    $check->recommendation,
                    $check->checked_at,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
