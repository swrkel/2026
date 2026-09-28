<?php

namespace Modules\MyHealthMembers\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Modules\MyHealthMembers\Services\Reports\MyHealthReportService;

class MyHealthReportController extends Controller
{
    public function __construct(private MyHealthReportService $reports) {}

    public function index(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date', 'search', 'status']);
        $summary = $this->reports->dashboard($filters);
        return view('myhealthmembers::reports.index', compact('summary', 'filters'));
    }

    public function patientHistory(Request $request) { return $this->render('patient_history', 'Patient History Report', $this->reports->patientHistory($request->all()), $request); }
    public function prescriptions(Request $request) { return $this->render('prescriptions', 'Prescription Report', $this->reports->prescriptions($request->all()), $request); }
    public function labs(Request $request) { return $this->render('labs', 'Lab Report', $this->reports->labs($request->all()), $request); }
    public function dispenses(Request $request) { return $this->render('dispenses', 'Medicine Dispense Report', $this->reports->dispenses($request->all()), $request); }
    public function claims(Request $request) { return $this->render('claims', 'Insurance Claim Report', $this->reports->claims($request->all()), $request); }
    public function telemedicine(Request $request) { return $this->render('telemedicine', 'Telemedicine Report', $this->reports->telemedicine($request->all()), $request); }
    public function revenue(Request $request) { return $this->render('revenue', 'Revenue Report', $this->reports->revenue($request->all()), $request); }
    public function doctorPerformance(Request $request) { return $this->render('doctor_performance', 'Doctor Performance Report', $this->reports->doctorPerformance($request->all()), $request); }

    public function export(Request $request, string $report)
    {
        [$headers, $rows, $title] = $this->reports->exportData($report, $request->all());
        $filename = 'my_health_' . str_replace('-', '_', $report) . '_' . now()->format('Ymd_His') . '.csv';

        $callback = function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function render(string $view, string $title, $rows, Request $request)
    {
        $filters = $request->only(['start_date', 'end_date', 'search', 'status']);
        $reportKey = str_replace('_', '-', $view);
        return view('myhealthmembers::reports.' . $view, compact('title', 'rows', 'filters', 'reportKey'));
    }
}
