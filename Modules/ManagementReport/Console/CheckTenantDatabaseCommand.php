<?php

namespace Modules\ManagementReport\Console;

use Illuminate\Console\Command;
use Modules\ManagementReport\Support\TenantConnection;

class CheckTenantDatabaseCommand extends Command
{
    protected $signature = 'management-report:tenant-check {tenant : Tenant ID to inspect}';

    protected $description = 'Verify the exact database used by Management Report for a tenant.';

    public function handle(): int
    {
        $tenantModel = config('tenancy.tenant_model', \App\Tenant::class);
        $tenant = $tenantModel::query()->find($this->argument('tenant'));

        if (!$tenant) {
            $this->error('Tenant not found.');
            return self::FAILURE;
        }

        try {
            if (tenancy()->initialized) {
                tenancy()->end();
            }

            TenantConnection::reset();
            tenancy()->initialize($tenant);
            TenantConnection::reset();
            TenantConnection::activate();

            $database = TenantConnection::databaseName();
            $schema = TenantConnection::schema();
            $tables = [
                'mgmt_report_templates',
                'mgmt_report_runs',
                'mgmt_report_run_sections',
                'mgmt_report_shares',
                'mgmt_report_share_recipients',
                'mgmt_report_settings',
                'mgmt_report_review_statuses',
            ];

            $this->info('Tenant ID: ' . $tenant->getKey());
            $this->info('Expected DB: ' . TenantConnection::expectedDatabaseName());
            $this->info('Connected DB: ' . $database);

            foreach ($tables as $table) {
                $this->line(sprintf('%-38s %s', $table, $schema->hasTable($table) ? 'OK' : 'MISSING'));
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            if (function_exists('tenancy') && tenancy()->initialized) {
                tenancy()->end();
            }
            TenantConnection::reset();
        }
    }
}
