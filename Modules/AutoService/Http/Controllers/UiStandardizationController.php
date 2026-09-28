<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\AutoService\Services\AutoServiceUiPerformanceAuditService;

class UiStandardizationController extends AutoServiceBaseController
{
    protected AutoServiceUiPerformanceAuditService $audit;

    public function __construct(AutoServiceUiPerformanceAuditService $audit)
    {
        $this->audit = $audit;
    }

    public function index(Request $request)
    {
        $businessId = $this->businessId();
        $locationId = $this->locationId();
        $routeChecks = $this->audit->routeChecks();
        $tableChecks = $this->audit->tableChecks();
        $performanceChecks = $this->audit->performanceChecks($businessId, $locationId);
        $counters = $this->audit->safeCounters($businessId, $locationId);
        $recentLogs = collect();
        try {
            $schema = Schema::connection($this->autoServiceTenantConnection());
            if ($schema->hasTable('auto_service_ui_audit_logs')) {
                $recentLogs = $this->tenantDb()->table('auto_service_ui_audit_logs')
                    ->when($businessId, fn($q) => $q->where('business_id', $businessId))
                    ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                    ->orderByDesc('id')->limit(30)->get();
            }
        } catch (\Throwable $e) { $recentLogs = collect(); }

        return view('autoservice::ui_standardization.index', compact('routeChecks','tableChecks','performanceChecks','counters','recentLogs'));
    }

    public function logCheck(Request $request)
    {
        $schema = Schema::connection($this->autoServiceTenantConnection());
        if (!$schema->hasTable('auto_service_ui_audit_logs')) {
            return redirect()->back()->with('status', 'Please run Stage 044 SQL first.');
        }

        $this->tenantDb()->table('auto_service_ui_audit_logs')->insert([
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'audit_area' => $request->input('audit_area', 'manual_stage_044_check'),
            'audit_status' => $request->input('audit_status', 'checked'),
            'notes' => $request->input('notes', 'Manual UI/performance audit check completed.'),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('status', 'Stage 044 audit check logged successfully.');
    }
}
