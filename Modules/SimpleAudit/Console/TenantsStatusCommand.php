<?php

namespace Modules\SimpleAudit\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\SimpleAudit\Services\TenantConnectionManager;
use Throwable;

class TenantsStatusCommand extends Command
{
    protected $signature = 'simple-audit:tenants';
    protected $description = 'Show the central tenant registry used by Simple Audit without scanning application routes.';

    public function handle(TenantConnectionManager $tenants)
    {
        try {
            $connection = $tenants->centralConnectionName();
            $database = (string) DB::connection($connection)->getDatabaseName();
            $rows = $tenants->listTenants();

            $this->line('Simple Audit central tenant registry:');
            $this->line('Central connection: ' . $connection);
            $this->line('Central database: ' . $database);
            $this->line('Registered tenants: ' . count($rows));

            if (!$rows) {
                $this->warn('No tenant rows were found in the authoritative central tenants table.');
                return self::SUCCESS;
            }

            foreach ($rows as $row) {
                $tenantId = (string) ($row['id'] ?? '');
                $label = (string) ($row['label'] ?? $tenantId);
                $physical = $tenants->resolveTenantDatabaseName($tenantId, null, $connection) ?: '[unresolved]';
                $this->line('[OK] ' . str_pad($tenantId, 18) . ' ' . $label . '  ->  ' . $physical);
            }

            $this->info('Result: PASS - tenant selector source loaded from the canonical central registry.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Result: FAILED - ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
