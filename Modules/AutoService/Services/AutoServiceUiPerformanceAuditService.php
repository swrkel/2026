<?php

namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class AutoServiceUiPerformanceAuditService
{
    public function tenantConnection(): string
    {
        return config('database.default');
    }

    public function routeChecks(): array
    {
        $routes = [
            'autoservice.dashboard' => 'Dashboard',
            'autoservice.command_centre.index' => 'Workshop Command Centre',
            'autoservice.receptions.index' => 'Reception',
            'autoservice.jobs.index' => 'Jobs',
            'autoservice.parts_labour.index' => 'Parts & Labour',
            'autoservice.service_flow.index' => 'Service Flow',
            'autoservice.billing_delivery.index' => 'Billing & Delivery',
            'autoservice.customer_care.index' => 'Customer Care',
            'autoservice.maintenance_planner.index' => 'Maintenance Planner',
            'autoservice.management_kpi.index' => 'Management KPI',
            'autoservice.inventory_control.index' => 'Inventory Control',
            'autoservice.advanced_vehicle_history.index' => 'Advanced Vehicle History',
            'autoservice.workshop_planning.index' => 'Workshop Planning',
            'autoservice.business_intelligence.index' => 'Business Intelligence',
            'autoservice.dealer_enterprise.index' => 'Dealer Enterprise',
            'autoservice.enterprise_integration.index' => 'Enterprise Integration',
            'autoservice.ui_standardization.index' => 'UI Standardization & Performance',
        ];

        $rows = [];
        foreach ($routes as $name => $label) {
            $rows[] = [
                'name' => $name,
                'label' => $label,
                'exists' => Route::has($name),
            ];
        }

        return $rows;
    }

    public function tableChecks(): array
    {
        $tables = [
            'auto_service_jobs' => 'Job cards and workflow',
            'auto_service_estimates' => 'Estimates and customer approvals',
            'auto_service_job_parts' => 'Parts/accessories usage',
            'auto_service_job_labours' => 'Labour billing',
            'auto_service_invoices' => 'Billing and delivery invoices',
            'auto_service_customer_alerts' => 'Customer portal alerts',
            'auto_service_maintenance_plans' => 'Maintenance planner',
            'auto_service_integration_bridge_logs' => 'ERP integration bridge',
            'auto_service_ui_audit_logs' => 'Stage 044 UI/performance audit logs',
        ];

        $schema = Schema::connection($this->tenantConnection());
        $rows = [];
        foreach ($tables as $table => $purpose) {
            $exists = false;
            try { $exists = $schema->hasTable($table); } catch (\Throwable $e) { $exists = false; }
            $rows[] = ['table' => $table, 'purpose' => $purpose, 'exists' => $exists];
        }
        return $rows;
    }

    public function performanceChecks(?int $businessId = null, ?int $locationId = null): array
    {
        $schema = Schema::connection($this->tenantConnection());
        $items = [
            ['table' => 'auto_service_jobs', 'columns' => ['business_id', 'location_id', 'status', 'created_at']],
            ['table' => 'auto_service_job_parts', 'columns' => ['business_id', 'location_id', 'job_id', 'product_id', 'used_at']],
            ['table' => 'auto_service_invoices', 'columns' => ['business_id', 'location_id', 'job_id', 'status', 'invoice_date']],
            ['table' => 'auto_service_appointments', 'columns' => ['business_id', 'location_id', 'appointment_date', 'status']],
            ['table' => 'auto_service_customer_alerts', 'columns' => ['business_id', 'location_id', 'customer_id', 'is_read']],
        ];

        $rows = [];
        foreach ($items as $item) {
            $exists = false;
            try { $exists = $schema->hasTable($item['table']); } catch (\Throwable $e) { $exists = false; }
            $missing = [];
            if ($exists) {
                foreach ($item['columns'] as $column) {
                    try { if (!$schema->hasColumn($item['table'], $column)) { $missing[] = $column; } } catch (\Throwable $e) { $missing[] = $column; }
                }
            }
            $rows[] = [
                'table' => $item['table'],
                'exists' => $exists,
                'missing_columns' => $missing,
                'status' => !$exists ? 'Missing table' : (empty($missing) ? 'Ready' : 'Missing recommended columns: '.implode(', ', $missing)),
            ];
        }
        return $rows;
    }

    public function safeCounters(?int $businessId = null, ?int $locationId = null): array
    {
        return [
            'open_jobs' => $this->countRows('auto_service_jobs', $businessId, $locationId, ['status' => ['open','received','in_progress','hold','waiting_parts','qc_pending']]),
            'unbilled_jobs' => $this->countRows('auto_service_jobs', $businessId, $locationId, ['status' => ['completed','qc_passed','ready_for_billing']]),
            'pending_customer_alerts' => $this->countRows('auto_service_customer_alerts', $businessId, $locationId, ['is_read' => [0, null]]),
            'pending_appointments' => $this->countRows('auto_service_appointments', $businessId, $locationId, ['status' => ['pending','requested']]),
        ];
    }

    protected function countRows(string $table, ?int $businessId, ?int $locationId, array $filters = []): int
    {
        try {
            $schema = Schema::connection($this->tenantConnection());
            if (!$schema->hasTable($table)) { return 0; }
            $query = DB::connection($this->tenantConnection())->table($table);
            if ($businessId && $schema->hasColumn($table, 'business_id')) { $query->where('business_id', $businessId); }
            if ($locationId && $schema->hasColumn($table, 'location_id')) { $query->where('location_id', $locationId); }
            foreach ($filters as $column => $values) {
                if (!$schema->hasColumn($table, $column)) { continue; }
                $query->where(function ($q) use ($column, $values) {
                    foreach ((array) $values as $value) {
                        is_null($value) ? $q->orWhereNull($column) : $q->orWhere($column, $value);
                    }
                });
            }
            return (int) $query->count();
        } catch (\Throwable $e) { return 0; }
    }
}
