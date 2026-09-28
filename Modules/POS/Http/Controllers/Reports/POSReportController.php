<?php

namespace Modules\POS\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\Reports\POSReportService;

class POSReportController extends Controller
{
    public function index(Request $request, POSReportService $service)
    {
        return view('pos::reports.index', $service->dashboard($request->all()));
    }

    public function export(Request $request, POSReportService $service, string $report)
    {
        $rows = $service->exportRows($report, $request->all());
        $filename = 'pos_' . str_replace('-', '_', $report) . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            if (empty($rows)) {
                fputcsv($handle, ['No data']);
            } else {
                fputcsv($handle, array_keys((array) $rows[0]));
                foreach ($rows as $row) {
                    fputcsv($handle, array_values((array) $row));
                }
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
