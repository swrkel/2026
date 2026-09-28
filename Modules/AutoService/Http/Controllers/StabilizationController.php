<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class StabilizationController extends AutoServiceBaseController
{
    /**
     * Stage 041 server stabilization and issue capture centre.
     * This is read-heavy and safe for multi-tenant / multi-business testing.
     */
    public function index()
    {
        $connection = $this->autoServiceTenantConnection();
        $schema = Schema::connection($connection);

        $routeNames = [
            'autoservice.dashboard',
            'autoservice.command_centre.index',
            'autoservice.receptions.index',
            'autoservice.vehicles.index',
            'autoservice.jobs.index',
            'autoservice.estimates.index',
            'autoservice.parts_labour.index',
            'autoservice.service_flow.index',
            'autoservice.billing_delivery.index',
            'autoservice.customer_portal.lookup',
            'autoservice.customer_care.index',
            'autoservice.maintenance_planner.index',
            'autoservice.management_kpi.index',
            'autoservice.inventory_control.index',
            'autoservice.advanced_vehicle_history.index',
            'autoservice.workshop_planning.index',
            'autoservice.business_intelligence.index',
            'autoservice.dealer_enterprise.index',
            'autoservice.production_audit.index',
            'autoservice.stabilization.index',
        ];

        $routeChecks = collect($routeNames)->map(function ($name) {
            return [
                'name' => $name,
                'exists' => Route::has($name),
                'url' => Route::has($name) ? route($name) : null,
            ];
        })->values()->all();

        $tableNames = [
            'auto_service_jobs',
            'auto_service_estimates',
            'auto_service_invoices',
            'auto_service_job_parts',
            'auto_service_job_labours',
            'auto_service_customer_alerts',
            'auto_service_notification_logs',
            'auto_service_reminders',
            'auto_service_warranty_claims',
            'auto_service_maintenance_plans',
            'auto_service_parts_reservations',
            'auto_service_bay_schedules',
            'auto_service_technician_schedules',
            'auto_service_fleet_contracts',
            'auto_service_server_test_issues',
        ];

        $tableChecks = [];
        foreach ($tableNames as $table) {
            $exists = false;
            $rows = 0;
            $businessScoped = false;
            $locationScoped = false;
            try {
                $exists = $schema->hasTable($table);
                if ($exists) {
                    $businessScoped = $schema->hasColumn($table, 'business_id');
                    $locationScoped = $schema->hasColumn($table, 'location_id');
                    $query = $this->tenantDb()->table($table);
                    if ($this->businessId() && $businessScoped) {
                        $query->where('business_id', $this->businessId());
                    }
                    if ($this->locationId() && $locationScoped) {
                        $query->where('location_id', $this->locationId());
                    }
                    $rows = (int) $query->count();
                }
            } catch (\Throwable $e) {
                $exists = false;
            }
            $tableChecks[] = compact('table', 'exists', 'rows', 'businessScoped', 'locationScoped');
        }

        $issueSummary = [
            'open' => $this->safeCount('auto_service_server_test_issues', fn($q) => $q->whereIn('status', ['open', 'new'])),
            'in_progress' => $this->safeCount('auto_service_server_test_issues', fn($q) => $q->where('status', 'in_progress')),
            'fixed' => $this->safeCount('auto_service_server_test_issues', fn($q) => $q->whereIn('status', ['fixed', 'closed'])),
            'critical' => $this->safeCount('auto_service_server_test_issues', fn($q) => $q->where('severity', 'critical')),
        ];

        $recentIssues = [];
        try {
            if ($schema->hasTable('auto_service_server_test_issues')) {
                $query = $this->tenantDb()->table('auto_service_server_test_issues');
                if ($this->businessId() && $schema->hasColumn('auto_service_server_test_issues', 'business_id')) {
                    $query->where('business_id', $this->businessId());
                }
                $recentIssues = $query->orderByDesc('id')->limit(20)->get();
            }
        } catch (\Throwable $e) {
            $recentIssues = collect();
        }

        $summary = [
            'tenant_connection' => $connection,
            'tenant_database' => $this->autoServiceTenantDatabaseName(),
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'missing_routes' => collect($routeChecks)->where('exists', false)->count(),
            'missing_tables' => collect($tableChecks)->where('exists', false)->count(),
            'open_issues' => $issueSummary['open'],
            'critical_issues' => $issueSummary['critical'],
        ];

        return view('autoservice::stabilization.index', compact('summary', 'routeChecks', 'tableChecks', 'issueSummary', 'recentIssues'));
    }

    public function storeIssue(Request $request)
    {
        $data = $request->validate([
            'page_url' => 'nullable|string|max:500',
            'issue_title' => 'required|string|max:255',
            'issue_description' => 'nullable|string|max:5000',
            'severity' => 'nullable|in:low,medium,high,critical',
            'screenshot_reference' => 'nullable|string|max:500',
            'log_reference' => 'nullable|string|max:500',
        ]);

        try {
            if (!Schema::connection($this->autoServiceTenantConnection())->hasTable('auto_service_server_test_issues')) {
                return back()->with('status', ['success' => 0, 'msg' => 'Server test issue table is missing. Please run Stage 041 SQL first.']);
            }

            $this->tenantDb()->table('auto_service_server_test_issues')->insert([
                'business_id' => $this->businessId(),
                'location_id' => $this->locationId(),
                'reported_by' => auth()->id(),
                'page_url' => $data['page_url'] ?? request()->headers->get('referer'),
                'issue_title' => $data['issue_title'],
                'issue_description' => $data['issue_description'] ?? null,
                'severity' => $data['severity'] ?? 'medium',
                'status' => 'open',
                'screenshot_reference' => $data['screenshot_reference'] ?? null,
                'log_reference' => $data['log_reference'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            return back()->with('status', ['success' => 0, 'msg' => $e->getMessage()]);
        }

        return back()->with('status', ['success' => 1, 'msg' => 'Auto Service server test issue captured successfully.']);
    }

    public function updateIssue(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:open,in_progress,fixed,closed,rejected',
            'developer_note' => 'nullable|string|max:5000',
        ]);

        try {
            $this->tenantDb()->table('auto_service_server_test_issues')->where('id', $id)->update([
                'status' => $data['status'],
                'developer_note' => $data['developer_note'] ?? null,
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            return back()->with('status', ['success' => 0, 'msg' => $e->getMessage()]);
        }

        return back()->with('status', ['success' => 1, 'msg' => 'Issue status updated.']);
    }

    protected function safeCount(string $table, ?callable $callback = null): int
    {
        try {
            $schema = Schema::connection($this->autoServiceTenantConnection());
            if (!$schema->hasTable($table)) {
                return 0;
            }
            $query = $this->tenantDb()->table($table);
            if ($this->businessId() && $schema->hasColumn($table, 'business_id')) {
                $query->where('business_id', $this->businessId());
            }
            if ($this->locationId() && $schema->hasColumn($table, 'location_id')) {
                $query->where('location_id', $this->locationId());
            }
            if ($callback) {
                $callback($query);
            }
            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
