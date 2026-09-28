<?php

namespace Modules\PumperDashboardNew\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\PumperDashboardNew\Entities\PonePdOperator;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Services\PoneReportService;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;
use Modules\PumperDashboardNew\Utils\PoneCsvExporter;

class ReportController extends Controller
{
    private const PERMISSIONS = [
        'shifts' => 'pumper_dashboard_new.reports.shifts',
        'payments' => 'pumper_dashboard_new.reports.payments',
        'meters' => 'pumper_dashboard_new.reports.meters',
        'other-sales' => 'pumper_dashboard_new.reports.other_sales',
        'unloads' => 'pumper_dashboard_new.reports.unloads',
        'day-entries' => 'pumper_dashboard_new.reports.day_entries',
        'collections' => 'pumper_dashboard_new.reports.collections',
        'ledger' => 'pumper_dashboard_new.reports.ledger',
        'shortages' => 'pumper_dashboard_new.reports.shortages',
        'commissions' => 'pumper_dashboard_new.reports.commissions',
        'print-logs' => 'pumper_dashboard_new.reports.print_logs',
        'audit' => 'pumper_dashboard_new.reports.audit',
    ];

    public function __construct(
        private PoneReportService $reports,
        private PoneCsvExporter $csv,
        private PoneSharedMasterDataService $masterData
    ) {}

    public function index(Request $request, string $report = 'shifts')
    {
        $labels = $this->reports->labels();
        $availableReports = collect($labels)
            ->filter(fn ($label, $key) => auth()->user()?->can(self::PERMISSIONS[$key]) ?? false)
            ->all();
        abort_if(empty($availableReports), 403);
        if (! $request->route('report')) $report = (string) array_key_first($availableReports);
        $this->guardReport($report);

        $businessId = $this->businessId();
        $filters = $request->only([
            'from', 'to', 'location_id', 'operator_profile_id', 'payment_type',
            'printable_type', 'action', 'status', 'limit',
        ]);
        $rows = $this->reports->report($report, $businessId, $filters);
        $operators = PonePdOperator::query()->where('business_id', $businessId)->orderBy('display_name')->get();
        $locations = $this->masterData->locations($businessId);
        return view('pumperdashboardnew::admin.reports.index', compact(
            'report', 'rows', 'filters', 'operators', 'locations', 'labels', 'availableReports'
        ));
    }

    public function export(Request $request, string $report)
    {
        $this->guardReport($report);
        $rows = $this->reports->report($report, $this->businessId(), $request->all());
        $filename = 'pumper-dashboard-new-' . $report . '-' . now()->format('Ymd-His') . '.csv';
        return $this->csv->download($rows, $filename);
    }

    private function guardReport(string $report): void
    {
        abort_unless(isset(self::PERMISSIONS[$report]), 404);
        abort_unless(auth()->user()?->can(self::PERMISSIONS[$report]), 403);
    }
}
