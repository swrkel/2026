<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Modules\AutoService\Services\AutoServiceContext;

class AutoServiceBaseController extends Controller
{
    /**
     * Core tenant tables required before any operational Auto Service page is opened.
     * Central Vehicle Registry tables are intentionally not listed here because they
     * must live in the central database, not in tenant databases.
     */
    protected array $autoServiceRequiredTenantTables = [
        'auto_service_vehicles',
        'auto_service_jobs',
        'auto_service_job_lines',
        'auto_service_job_mechanics',
        'auto_service_mechanics',
        'auto_service_appointments',
        'auto_service_estimates',
        'auto_service_estimate_lines',
        'auto_service_invoices',
        'auto_service_invoice_lines',
        'auto_service_payments',
        'auto_service_service_packages',
        'auto_service_package_lines',
        'auto_service_labour_items',
        'auto_service_part_movements',
        'auto_service_receptions',
        'auto_service_inspections',
        'auto_service_inspection_items',
        'auto_service_documents',
        'auto_service_timeline',
        'auto_service_reminders',
        'auto_service_settings',
        'auto_service_approval_requests',
        'auto_service_notification_logs',
        'auto_service_bays',
        'auto_service_bay_allocations',
        'auto_service_communications',
        'auto_service_feedback',
        'auto_service_quality_checks',
        'auto_service_deliveries',
    ];

    protected string $autoServiceOriginalConnection = '';

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->activateAutoServiceTenantConnection();

            $missing = $this->missingAutoServiceTenantTables();

            if (!empty($missing)) {
                return response()->view('autoservice::dashboard.setup_required', [
                    'missing_tables' => $missing,
                    'required_tables' => $this->autoServiceRequiredTenantTables,
                    'autoservice_hide_nav' => true,
                    'checked_connection' => $this->autoServiceTenantConnection(),
                    'checked_database' => $this->autoServiceTenantDatabaseName(),
                ]);
            }

            return $next($request);
        });
    }

    /**
     * Auto Service is tenant operational data. In this ERP, tenant tables live on
     * mysql_tenant after tenant_db() resolves the current domain/business database.
     * Earlier setup checks used the default connection, which could still point to
     * the central/base DB and incorrectly report missing tables.
     */
    protected function activateAutoServiceTenantConnection(): void
    {
        $this->autoServiceOriginalConnection = config('database.default');

        try {
            if (function_exists('tenant_db')) {
                tenant_db();
            }

            if ($this->hasConfiguredTenantConnection()) {
                DB::setDefaultConnection('mysql_tenant');
            }
        } catch (\Throwable $e) {
            // Keep the current/default connection if tenant resolution is not available.
        }
    }

    protected function hasConfiguredTenantConnection(): bool
    {
        try {
            return !empty(config('database.connections.mysql_tenant.database'));
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function autoServiceTenantConnection(): string
    {
        return $this->hasConfiguredTenantConnection() ? 'mysql_tenant' : config('database.default');
    }

    protected function autoServiceTenantDatabaseName(): ?string
    {
        try {
            return DB::connection($this->autoServiceTenantConnection())->getDatabaseName();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function tenantDb()
    {
        return DB::connection($this->autoServiceTenantConnection());
    }

    protected function businessId()
    {
        return app(AutoServiceContext::class)->businessId();
    }

    protected function locationId()
    {
        return app(AutoServiceContext::class)->locationId();
    }

    protected function missingAutoServiceTenantTables(): array
    {
        $connection = $this->autoServiceTenantConnection();
        $database = $this->autoServiceTenantDatabaseName();

        if (empty($database)) {
            return $this->autoServiceRequiredTenantTables;
        }

        $cacheKey = 'autoservice:schema:' . sha1($connection . '|' . $database . '|' . implode('|', $this->autoServiceRequiredTenantTables));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($connection, $database) {
            try {
                // One information_schema query replaces 30+ Schema::hasTable calls on every page request.
                $rows = DB::connection($connection)
                    ->table('information_schema.tables')
                    ->where('table_schema', $database)
                    ->whereIn('table_name', $this->autoServiceRequiredTenantTables)
                    ->pluck('table_name')
                    ->all();

                $existing = array_fill_keys($rows, true);

                return array_values(array_filter(
                    $this->autoServiceRequiredTenantTables,
                    static fn (string $table): bool => !isset($existing[$table])
                ));
            } catch (\Throwable $e) {
                // Do not run another series of information_schema calls when the DB is unavailable.
                return $this->autoServiceRequiredTenantTables;
            }
        });
    }
}
