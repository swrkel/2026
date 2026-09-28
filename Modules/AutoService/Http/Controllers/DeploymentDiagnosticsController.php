<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DeploymentDiagnosticsController extends AutoServiceBaseController
{
    /**
     * Stage 042 deployment diagnostics for first server rollout.
     * Read-only checks except the optional SQL acknowledgement table created by raw SQL.
     */
    public function index()
    {
        $connection = $this->autoServiceTenantConnection();
        $database = $this->autoServiceTenantDatabaseName();
        $schema = Schema::connection($connection);

        $routeNames = [
            'autoservice.dashboard',
            'autoservice.command_centre.index',
            'autoservice.workspace.index',
            'autoservice.receptions.index',
            'autoservice.vehicles.index',
            'autoservice.jobs.index',
            'autoservice.estimates.index',
            'autoservice.parts_labour.index',
            'autoservice.service_flow.index',
            'autoservice.billing_delivery.index',
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
            'autoservice.deployment_diagnostics.index',
        ];

        $tableNames = [
            'auto_service_vehicles',
            'auto_service_jobs',
            'auto_service_estimates',
            'auto_service_invoices',
            'auto_service_payments',
            'auto_service_job_parts',
            'auto_service_job_labours',
            'auto_service_timeline',
            'auto_service_documents',
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
            'auto_service_deployment_diagnostic_runs',
        ];

        $routeChecks = collect($routeNames)->map(function ($name) {
            return [
                'name' => $name,
                'exists' => Route::has($name),
                'url' => Route::has($name) ? route($name) : null,
            ];
        })->values();

        $tableChecks = collect($tableNames)->map(function ($table) use ($schema) {
            $exists = false;
            $rows = 0;
            $businessScoped = false;
            $locationScoped = false;
            $hasTimestamps = false;
            try {
                $exists = $schema->hasTable($table);
                if ($exists) {
                    $businessScoped = $schema->hasColumn($table, 'business_id');
                    $locationScoped = $schema->hasColumn($table, 'location_id');
                    $hasTimestamps = $schema->hasColumn($table, 'created_at') && $schema->hasColumn($table, 'updated_at');
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

            return compact('table', 'exists', 'rows', 'businessScoped', 'locationScoped', 'hasTimestamps');
        })->values();

        $queueChecks = [
            'pending_jobs' => $this->safeStatusCount('auto_service_jobs', ['pending', 'received', 'inspection', 'repair']),
            'completed_jobs' => $this->safeStatusCount('auto_service_jobs', ['completed', 'delivered', 'closed']),
            'unpaid_invoices' => $this->safeStatusCount('auto_service_invoices', ['unpaid', 'partial', 'pending']),
            'open_issues' => $this->safeStatusCount('auto_service_server_test_issues', ['open', 'new', 'in_progress']),
        ];

        $recentDiagnosticRuns = collect();
        try {
            if ($schema->hasTable('auto_service_deployment_diagnostic_runs')) {
                $recentDiagnosticRuns = $this->tenantDb()->table('auto_service_deployment_diagnostic_runs')
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get();
            }
        } catch (\Throwable $e) {
            $recentDiagnosticRuns = collect();
        }

        $summary = [
            'tenant_connection' => $connection,
            'tenant_database' => $database,
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'missing_routes' => $routeChecks->where('exists', false)->count(),
            'missing_tables' => $tableChecks->where('exists', false)->count(),
            'tables_without_business_scope' => $tableChecks->where('exists', true)->where('businessScoped', false)->count(),
            'tables_without_timestamps' => $tableChecks->where('exists', true)->where('hasTimestamps', false)->count(),
        ];

        $this->recordDiagnosticRun($summary, $queueChecks);

        return view('autoservice::deployment_diagnostics.index', compact('summary', 'routeChecks', 'tableChecks', 'queueChecks', 'recentDiagnosticRuns'));
    }

    protected function safeStatusCount(string $table, array $statuses): int
    {
        try {
            $schema = Schema::connection($this->autoServiceTenantConnection());
            if (!$schema->hasTable($table)) {
                return 0;
            }

            $query = $this->tenantDb()->table($table);
            if ($schema->hasColumn($table, 'status')) {
                $query->whereIn('status', $statuses);
            }
            if ($this->businessId() && $schema->hasColumn($table, 'business_id')) {
                $query->where('business_id', $this->businessId());
            }
            if ($this->locationId() && $schema->hasColumn($table, 'location_id')) {
                $query->where('location_id', $this->locationId());
            }

            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function recordDiagnosticRun(array $summary, array $queueChecks): void
    {
        try {
            if (!Schema::connection($this->autoServiceTenantConnection())->hasTable('auto_service_deployment_diagnostic_runs')) {
                return;
            }

            $this->tenantDb()->table('auto_service_deployment_diagnostic_runs')->insert([
                'business_id' => $this->businessId(),
                'location_id' => $this->locationId(),
                'run_by' => auth()->id(),
                'tenant_connection' => $summary['tenant_connection'] ?? null,
                'tenant_database' => $summary['tenant_database'] ?? null,
                'missing_routes' => $summary['missing_routes'] ?? 0,
                'missing_tables' => $summary['missing_tables'] ?? 0,
                'open_issues' => $queueChecks['open_issues'] ?? 0,
                'payload_json' => json_encode(['summary' => $summary, 'queue_checks' => $queueChecks]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Diagnostics must not block page loading.
        }
    }
}
