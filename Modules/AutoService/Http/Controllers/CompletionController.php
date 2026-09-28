<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Support\Facades\Schema;

class CompletionController extends AutoServiceBaseController
{
    /**
     * Production readiness page for Auto Service.
     * This is intentionally read-only and tenant safe. It helps server testers
     * confirm that routes, required tenant tables, business scoping and queue-like
     * operational records are present before live use.
     */
    public function index()
    {
        $connection = $this->autoServiceTenantConnection();
        $db = $this->tenantDb();
        $businessId = $this->businessId();
        $locationId = $this->locationId();

        $tenantTables = $this->autoServiceRequiredTenantTables;
        $tableStatus = [];

        foreach ($tenantTables as $table) {
            $exists = false;
            $rows = null;
            try {
                $exists = Schema::connection($connection)->hasTable($table);
                if ($exists) {
                    $q = $db->table($table);
                    if ($businessId && Schema::connection($connection)->hasColumn($table, 'business_id')) {
                        $q->where('business_id', $businessId);
                    }
                    if ($locationId && Schema::connection($connection)->hasColumn($table, 'location_id')) {
                        $q->where('location_id', $locationId);
                    }
                    $rows = $q->count();
                }
            } catch (\Throwable $e) {
                $exists = false;
            }

            $tableStatus[] = [
                'table' => $table,
                'exists' => $exists,
                'rows' => $rows,
            ];
        }

        $summary = [
            'tenant_connection' => $connection,
            'tenant_database' => $this->autoServiceTenantDatabaseName(),
            'business_id' => $businessId,
            'location_id' => $locationId,
            'required_tables' => count($tenantTables),
            'missing_tables' => collect($tableStatus)->where('exists', false)->count(),
            'open_jobs' => $this->safeCount('auto_service_jobs', function ($q) {
                return $q->whereNotIn('status', ['delivered', 'cancelled']);
            }),
            'pending_reminders' => $this->safeCount('auto_service_reminders', function ($q) {
                return $q->where('status', 'pending');
            }),
            'pending_notifications' => $this->safeCount('auto_service_notification_logs', function ($q) {
                return $q->whereIn('status', ['pending', 'queued']);
            }),
            'pending_communications' => $this->safeCount('auto_service_communications', function ($q) {
                return $q->whereIn('status', ['pending', 'queued']);
            }),
        ];

        $routes = [
            'autoservice.dashboard' => route('autoservice.dashboard'),
            'autoservice.jobs.index' => route('autoservice.jobs.index'),
            'autoservice.vehicles.index' => route('autoservice.vehicles.index'),
            'autoservice.receptions.index' => route('autoservice.receptions.index'),
            'autoservice.workshop.index' => route('autoservice.workshop.index'),
            'autoservice.quality_control.index' => route('autoservice.quality_control.index'),
            'autoservice.deliveries.index' => route('autoservice.deliveries.index'),
            'autoservice.reports.index' => route('autoservice.reports.index'),
            'autoservice.completion.index' => route('autoservice.completion.index'),
        ];

        return view('autoservice::completion.index', compact('summary', 'tableStatus', 'routes'));
    }

    protected function safeCount(string $table, ?callable $callback = null): int
    {
        try {
            $connection = $this->autoServiceTenantConnection();
            if (!Schema::connection($connection)->hasTable($table)) {
                return 0;
            }

            $q = $this->tenantDb()->table($table);
            $businessId = $this->businessId();
            $locationId = $this->locationId();

            if ($businessId && Schema::connection($connection)->hasColumn($table, 'business_id')) {
                $q->where('business_id', $businessId);
            }
            if ($locationId && Schema::connection($connection)->hasColumn($table, 'location_id')) {
                $q->where('location_id', $locationId);
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
