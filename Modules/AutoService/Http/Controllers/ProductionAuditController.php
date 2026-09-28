<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class ProductionAuditController extends AutoServiceBaseController
{
    /**
     * Final production audit page for Stage 040.
     * Read-only, tenant-safe, and business/location scoped where table columns exist.
     */
    public function index()
    {
        $connection = $this->autoServiceTenantConnection();
        $schema = Schema::connection($connection);

        $requiredTables = array_values(array_unique(array_merge($this->autoServiceRequiredTenantTables, [
            'auto_service_customer_alerts',
            'auto_service_warranty_claims',
            'auto_service_maintenance_plans',
            'auto_service_reorder_requests',
            'auto_service_fleet_customers',
            'auto_service_fleet_contracts',
            'auto_service_fleet_drivers',
            'auto_service_corporate_pricing',
            'auto_service_workshop_plans',
            'auto_service_technician_schedules',
            'auto_service_bay_schedules',
            'auto_service_parts_reservations',
        ])));

        $tableChecks = [];
        foreach ($requiredTables as $table) {
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
            'autoservice.customer_care.index',
            'autoservice.maintenance_planner.index',
            'autoservice.inventory_control.index',
            'autoservice.advanced_vehicle_history.index',
            'autoservice.workshop_planning.index',
            'autoservice.business_intelligence.index',
            'autoservice.dealer_enterprise.index',
            'autoservice.completion.index',
            'autoservice.production_audit.index',
        ];

        $routeChecks = [];
        foreach ($routeNames as $name) {
            $routeChecks[] = [
                'name' => $name,
                'exists' => Route::has($name),
                'url' => Route::has($name) ? route($name) : null,
            ];
        }

        $operational = [
            'open_jobs' => $this->safeCount('auto_service_jobs', fn($q) => $q->whereNotIn('status', ['delivered', 'cancelled'])),
            'jobs_waiting_approval' => $this->safeCount('auto_service_jobs', fn($q) => $q->whereIn('status', ['estimate_sent', 'waiting_customer_approval', 'pending_approval'])),
            'jobs_ready_delivery' => $this->safeCount('auto_service_jobs', fn($q) => $q->whereIn('status', ['ready_for_delivery', 'completed'])),
            'unpaid_invoices' => $this->safeCount('auto_service_invoices', fn($q) => $q->whereIn('status', ['unpaid', 'partial', 'pending'])),
            'pending_reminders' => $this->safeCount('auto_service_reminders', fn($q) => $q->where('status', 'pending')),
            'pending_notifications' => $this->safeCount('auto_service_notification_logs', fn($q) => $q->whereIn('status', ['pending', 'queued'])),
            'pending_warranty_claims' => $this->safeCount('auto_service_warranty_claims', fn($q) => $q->whereIn('status', ['pending', 'open', 'submitted'])),
            'fleet_contracts_active' => $this->safeCount('auto_service_fleet_contracts', fn($q) => $q->where('status', 'active')),
        ];

        $summary = [
            'tenant_connection' => $connection,
            'tenant_database' => $this->autoServiceTenantDatabaseName(),
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'tables_total' => count($tableChecks),
            'tables_missing' => collect($tableChecks)->where('exists', false)->count(),
            'routes_total' => count($routeChecks),
            'routes_missing' => collect($routeChecks)->where('exists', false)->count(),
            'business_scope_gaps' => collect($tableChecks)->where('exists', true)->where('businessScoped', false)->count(),
        ];

        return view('autoservice::production_audit.index', compact('summary', 'tableChecks', 'routeChecks', 'operational'));
    }

    protected function safeCount(string $table, ?callable $callback = null): int
    {
        try {
            $connection = $this->autoServiceTenantConnection();
            if (!Schema::connection($connection)->hasTable($table)) {
                return 0;
            }

            $q = $this->tenantDb()->table($table);
            if ($this->businessId() && Schema::connection($connection)->hasColumn($table, 'business_id')) {
                $q->where('business_id', $this->businessId());
            }
            if ($this->locationId() && Schema::connection($connection)->hasColumn($table, 'location_id')) {
                $q->where('location_id', $this->locationId());
            }
            if ($callback) {
                $q = $callback($q) ?: $q;
            }
            return (int) $q->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
