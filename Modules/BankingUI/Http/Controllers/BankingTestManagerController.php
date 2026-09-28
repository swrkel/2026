<?php

namespace Modules\BankingUI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\BankingUI\Services\BankingTestManagerService;

class BankingTestManagerController extends Controller
{
    protected $service;

    public function __construct(BankingTestManagerService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->dashboard();
        $prioritySummary = $this->service->prioritySummary();
        return view('bankingui::test_manager.index', compact('data', 'prioritySummary'));
    }

    public function coverage()
    {
        $rows = $this->service->coverageRows();
        return view('bankingui::test_manager.coverage', compact('rows'));
    }

    public function routes()
    {
        $rows = $this->service->routeHealthRows();
        return view('bankingui::test_manager.routes', compact('rows'));
    }

    public function issues()
    {
        $issues = collect();
        if (DB::getSchemaBuilder()->hasTable('banking_test_issues')) {
            $issues = DB::table('banking_test_issues')
                ->where('business_id', $this->service->businessId())
                ->latest('id')
                ->paginate(50);
        }
        $modules = $this->service->modules();
        return view('bankingui::test_manager.issues', compact('issues', 'modules'));
    }

    public function storeIssue(Request $request)
    {
        $data = $request->validate([
            'module_key' => 'required|string|max:100',
            'title' => 'required|string|max:191',
            'priority' => 'required|string|max:30',
            'page_name' => 'nullable|string|max:150',
            'route_name' => 'nullable|string|max:150',
            'steps_to_reproduce' => 'nullable|string',
            'expected_result' => 'nullable|string',
            'actual_result' => 'nullable|string',
        ]);
        if (!DB::getSchemaBuilder()->hasTable('banking_test_issues')) {
            return back()->with('status', ['success' => 0, 'msg' => 'Please run migrations first.']);
        }
        $nextId = (int) DB::table('banking_test_issues')->max('id') + 1;
        $data['business_id'] = $this->service->businessId();
        $data['issue_no'] = 'BKG-ISS-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
        $data['status'] = 'Open';
        $data['reported_by'] = optional(auth()->user())->id;
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('banking_test_issues')->insert($data);
        return back()->with('status', ['success' => 1, 'msg' => 'Banking tester issue added.']);
    }

    public function updateCoverage(Request $request)
    {
        if (!DB::getSchemaBuilder()->hasTable('banking_test_module_statuses')) {
            return back()->with('status', ['success' => 0, 'msg' => 'Please run migrations first.']);
        }
        $payload = $request->input('coverage', []);
        foreach ($payload as $id => $row) {
            DB::table('banking_test_module_statuses')->where('id', $id)->update([
                'status' => $row['status'] ?? 'Ready for UI Testing',
                'development_percent' => min(100, max(0, (int) ($row['development_percent'] ?? 0))),
                'ui_tested_percent' => min(100, max(0, (int) ($row['ui_tested_percent'] ?? 0))),
                'uat_percent' => min(100, max(0, (int) ($row['uat_percent'] ?? 0))),
                'production_ready_percent' => min(100, max(0, (int) ($row['production_ready_percent'] ?? 0))),
                'notes' => $row['notes'] ?? null,
                'updated_by' => optional(auth()->user())->id,
                'updated_at' => now(),
            ]);
        }
        return back()->with('status', ['success' => 1, 'msg' => 'Coverage updated.']);
    }
}
