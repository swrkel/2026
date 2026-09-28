<?php

namespace Modules\SimpleAudit\Console;

use Illuminate\Console\Command;
use Modules\SimpleAudit\Services\SchemaInstaller;
use Modules\SimpleAudit\Services\TenantConnectionManager;
use Throwable;

class SnapshotStockCommand extends Command
{
    protected $signature = 'simple-audit:snapshot-stock {--tenant= : Tenant ID} {--force : Add another complete baseline snapshot}';
    protected $description = 'Seed a Simple Audit stock baseline from variation_location_details.';

    public function handle(TenantConnectionManager $tenants, SchemaInstaller $installer)
    {
        try {
            $connection = $tenants->connectionForTenant($this->option('tenant') ?: null);
            $count = $installer->seedStockSnapshots($connection, (bool) $this->option('force'));
            $this->info('Stock baseline rows available: ' . $count);
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
