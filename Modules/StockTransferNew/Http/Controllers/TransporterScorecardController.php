<?php

namespace Modules\StockTransferNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Services\TransporterScorecardService;

class TransporterScorecardController extends Controller
{
    protected $service;

    public function __construct(TransporterScorecardService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['business_id', 'location_id', 'store_id', 'transporter_name', 'date_from', 'date_to']);
        $summary = $this->service->summary($filters);
        $rows = $this->service->scorecardRows($filters);

        if ($request->ajax()) {
            return response()->json(['summary' => $summary, 'data' => $rows]);
        }

        return view('stocktransfernew::transporter_scorecard.index', compact('summary', 'rows', 'filters'));
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->service->scorecardRows($request->all());
        $filename = 'stock_transfer_transporter_scorecard_' . now()->format('Ymd_His') . '.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="' . $filename . '"'];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Transporter', 'Transfers', 'On Time', 'Delayed', 'Damage Claims', 'Freight Variance', 'Score']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['transporter_name'], $row['transfer_count'], $row['on_time_count'], $row['delayed_count'],
                    $row['damage_claim_count'], $row['freight_variance'], $row['score'],
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }
}
