<?php

namespace Modules\SimpleAudit\Console;

use Illuminate\Console\Command;
use Modules\SimpleAudit\Services\SchemaInstaller;
use Modules\SimpleAudit\Services\TenantConnectionManager;
use Throwable;

class InstallSimpleAuditCommand extends Command
{
    protected $signature = 'simple-audit:install {--tenant=* : Tenant ID(s) to install} {--all-tenants : Install on every central tenant} {--no-triggers : Do not install capture triggers} {--no-snapshot : Do not seed stock baseline}';
    protected $description = 'Install Simple Audit sau_ tables, capture triggers and stock baseline in tenant database(s).';

    public function handle(TenantConnectionManager $tenants, SchemaInstaller $installer)
    {
        $ids = (array) $this->option('tenant');
        if ($this->option('all-tenants')) {
            $ids = array_map(function ($row) { return $row['id']; }, $tenants->listTenants());
        }

        $withTriggers = !$this->option('no-triggers');
        $seed = !$this->option('no-snapshot');

        if (!$ids) {
            try {
                $connection = $tenants->connectionForTenant(null);
                $installer->install($connection, $withTriggers, $seed);
                $this->info('Simple Audit installed on current tenant connection [' . $connection . '].');
                return self::SUCCESS;
            } catch (Throwable $e) {
                $this->error($e->getMessage());
                $this->line('Use --tenant=<tenant-id> or --all-tenants when running from the central application.');
                return self::FAILURE;
            }
        }

        $failed = 0;
        foreach (array_unique($ids) as $id) {
            try {
                $this->line('Installing tenant: ' . $id);
                $connection = $tenants->connectionForTenant($id);
                $installer->install($connection, $withTriggers, $seed);
                $this->info('  OK');
            } catch (Throwable $e) {
                $failed++;
                $this->error('  FAILED: ' . $e->getMessage());
            }
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
