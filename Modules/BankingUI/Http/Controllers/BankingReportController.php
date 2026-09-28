<?php

namespace Modules\BankingUI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\BankingUI\Services\BankingReportRegistryService;

class BankingReportController extends Controller
{
    public function index(BankingReportRegistryService $registry)
    {
        return view('bankingui::reports.index', [
            'groups' => $registry->groups(),
            'reports' => $registry->allReports(),
        ]);
    }

    public function show(string $reportKey, Request $request, BankingReportRegistryService $registry)
    {
        $reports = $registry->allReports();
        abort_unless(isset($reports[$reportKey]), 404);

        $this->audit($reportKey, null, $request);

        return view('bankingui::reports.show', [
            'report' => $reports[$reportKey],
            'filters' => $request->only(['date_from', 'date_to', 'location_id', 'search']),
        ]);
    }

    public function export(string $reportKey, string $type, Request $request, BankingReportRegistryService $registry)
    {
        $reports = $registry->allReports();
        abort_unless(isset($reports[$reportKey]), 404);
        abort_unless(in_array($type, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $this->audit($reportKey, $type, $request);

        return response()->json([
            'success' => true,
            'message' => 'Export request captured. Connect this endpoint to the final export engine.',
            'report' => $reportKey,
            'type' => $type,
        ]);
    }

    private function audit(string $reportKey, ?string $exportType, Request $request): void
    {
        if (!DB::getSchemaBuilder()->hasTable('bkg_ui_report_audit_logs')) {
            return;
        }

        DB::table('bkg_ui_report_audit_logs')->insert([
            'business_id' => session('business.id'),
            'location_id' => $request->input('location_id'),
            'user_id' => optional($request->user())->id,
            'report_key' => $reportKey,
            'export_type' => $exportType,
            'filters' => json_encode($request->except(['_token'])),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
