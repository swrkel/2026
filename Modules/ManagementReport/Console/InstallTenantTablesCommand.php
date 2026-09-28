<?php

namespace Modules\ManagementReport\Console;

use Illuminate\Console\Command;
use Modules\ManagementReport\Services\Installation\TenantSchemaInstaller;
use Modules\ManagementReport\Support\TenantConnection;

class InstallTenantTablesCommand extends Command
{
    protected $signature = 'management-report:install-tenants
                            {--tenant=* : Install only the specified tenant ID(s)}';

    protected $description = 'Create all Management Report mgmt_* tables in each dynamic tenant database.';

    public function handle(TenantSchemaInstaller $installer): int
    {
        $tenantModel = config('tenancy.tenant_model', \App\Tenant::class);
        $requested = array_values(array_filter((array) $this->option('tenant')));
        $query = $tenantModel::query();

        if ($requested !== []) {
            $query->whereIn('id', $requested);
        }

        $tenants = $query->get();
        if ($tenants->isEmpty()) {
            if (TenantConnection::isStandaloneTenantDatabase()) {
                return $this->installCurrentStandaloneDatabase($installer);
            }

            $this->warn('No matching tenants were found.');
            return self::SUCCESS;
        }

        $installed = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            try {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }

                TenantConnection::reset();
                tenancy()->initialize($tenant);
                TenantConnection::reset();
                TenantConnection::activate();
                $installer->install();

                $this->info(sprintf(
                    'Installed tenant %s in database %s',
                    $tenant->getKey(),
                    TenantConnection::databaseName()
                ));
                $installed++;
            } catch (\Throwable $e) {
                $this->error(sprintf('Tenant %s failed: %s', $tenant->getKey(), $e->getMessage()));
                $failed++;
            } finally {
                if (function_exists('tenancy') && tenancy()->initialized) {
                    tenancy()->end();
                }
                TenantConnection::reset();
            }
        }

        $this->newLine();
        $this->line(sprintf('Installed: %d | Failed: %d', $installed, $failed));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function installCurrentStandaloneDatabase(TenantSchemaInstaller $installer): int
    {
        try {
            TenantConnection::reset();
            TenantConnection::activate();
            $installer->install();

            $this->info(sprintf(
                'Installed standalone tenant database %s',
                TenantConnection::databaseName()
            ));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error(sprintf(
                'Standalone tenant database failed: %s',
                $e->getMessage()
            ));

            return self::FAILURE;
        } finally {
            TenantConnection::reset();
        }
    }
}
