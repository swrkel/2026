<?php

namespace Modules\StockTransferNew\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Services\Admin\CostReconciliationService;

class CostReconciliationController extends Controller
{
    protected CostReconciliationService $service;

    public function __construct(CostReconciliationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['business_id', 'from_location_id', 'to_location_id', 'store_id', 'status', 'date_from', 'date_to']);
        $summary = $this->service->summary($filters);
        $rows = $this->service->rows($filters);

        return view('stocktransfernew::admin.cost_reconciliation.index', compact('filters', 'summary', 'rows'));
    }

    public function export(Request $request)
    {
        $filters = $request->only(['business_id', 'from_location_id', 'to_location_id', 'store_id', 'status', 'date_from', 'date_to']);
        $rows = $this->service->rows($filters, 10000);

        $filename = 'stock_transfer_cost_reconciliation_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Transfer No', 'From Location', 'To Location', 'Store', 'Items', 'Transfer Cost', 'Received Cost', 'Variance', 'Status']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->transaction_date,
                    $row->transfer_no,
                    $row->from_location_name,
                    $row->to_location_name,
                    $row->store_name,
                    $row->item_count,
                    number_format((float) $row->transfer_cost, 4, '.', ''),
                    number_format((float) $row->received_cost, 4, '.', ''),
                    number_format((float) $row->cost_variance, 4, '.', ''),
                    $row->status,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
