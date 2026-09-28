<?php

namespace Modules\StockTransferNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Services\ProductionValidationService;

class ProductionValidationController extends Controller
{
    protected ProductionValidationService $service;

    public function __construct(ProductionValidationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $data = $this->service->dashboard($request->only(['business_id', 'location_id', 'store_id']));

        return view('stocktransfernew::production_validation.index', $data);
    }

    public function run(Request $request)
    {
        $run = $this->service->run($request->only(['business_id', 'location_id', 'store_id']));

        return redirect()
            ->route('stocktransfernew.production-validation.index')
            ->with('status', 'Production validation completed with status: '.$run->status);
    }

    public function resolve(Request $request, int $issueId)
    {
        $this->service->resolveIssue($issueId, (string) $request->input('resolved_note', ''));

        return redirect()
            ->route('stocktransfernew.production-validation.index')
            ->with('status', 'Validation issue marked as resolved.');
    }

    public function export(Request $request)
    {
        $data = $this->service->dashboard($request->only(['business_id', 'location_id', 'store_id']));
        $fileName = 'stock_transfer_production_validation_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Check Key', 'Title', 'Severity', 'Message', 'Recommended Action', 'Resolved']);

            foreach ($data['issues'] as $issue) {
                fputcsv($out, [
                    $issue->check_key,
                    $issue->title,
                    $issue->severity,
                    $issue->message,
                    $issue->recommended_action,
                    $issue->is_resolved ? 'Yes' : 'No',
                ]);
            }

            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
