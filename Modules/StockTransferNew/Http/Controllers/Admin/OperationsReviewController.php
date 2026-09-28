<?php

namespace Modules\StockTransferNew\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\Admin\OperationsReviewService;

class OperationsReviewController extends Controller
{
    protected $review;

    public function __construct(OperationsReviewService $review)
    {
        $this->review = $review;
    }

    public function index(Request $request)
    {
        $businessId = (int) session('user.business_id');
        $filters = $request->only(['from_date', 'to_date', 'location_id', 'store_id', 'severity']);
        $summary = $this->review->summary($businessId, $filters);
        $findings = $this->review->findings($businessId, $filters);

        return view('stocktransfernew::admin.operations_review.index', compact('summary', 'findings', 'filters'));
    }

    public function exportCsv(Request $request)
    {
        $businessId = (int) session('user.business_id');
        $filters = $request->only(['from_date', 'to_date', 'location_id', 'store_id', 'severity']);
        $rows = $this->review->findings($businessId, $filters, false);

        $filename = 'stock_transfer_operations_review_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Severity', 'Area', 'Reference', 'Issue', 'Recommendation']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['severity'] ?? '',
                    $row['area'] ?? '',
                    $row['reference'] ?? '',
                    $row['issue'] ?? '',
                    $row['recommendation'] ?? '',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
