<?php

namespace Modules\Audit\Console\Commands;

use Illuminate\Console\Command;
use Modules\Audit\Services\AuditContext;
use Modules\Audit\Services\AuditEngine;
use Modules\Audit\Services\TenantContextManager;

class AuditRunCommand extends Command
{
    protected $signature = 'audit:run
        {--tenant= : Tenant ID or tenant domain. Required for CLI unless tenancy is already initialized.}
        {--business= : Optional business ID filter}
        {--location= : Optional location ID filter}
        {--module=* : Limit the run to one or more Audit module groups}
        {--rule=* : Limit the run to one or more exact Audit rule codes}';

    protected $description = 'Run standalone Audit checks inside an explicitly initialized tenant context.';

    public function handle(AuditEngine $engine, TenantContextManager $tenants)
    {
        $initializedHere = false;

        try {
            if (!$tenants->initialized()) {
                $identifier = trim((string) $this->option('tenant'));
                if ($identifier === '') {
                    $this->error('No tenant context is active. Run with --tenant=<tenant-id-or-domain>.');
                    $this->line('Example: php artisan audit:run --tenant=ishadi-pd');
                    return 2;
                }

                $tenant = $tenants->resolve($identifier);
                if (!$tenant) {
                    $this->error('Tenant could not be resolved from: '.$identifier);
                    return 2;
                }

                $tenants->initialize($tenant);
                $initializedHere = true;
            }

            $tenantKey = $tenants->currentTenantKey();
            $database = $tenants->currentDatabase();
            if (!$tenantKey) {
                $this->error('Audit refused to run because the tenant context is still not initialized.');
                return 2;
            }

            $this->line('Tenant: '.$tenantKey.' | Database: '.($database ?: 'unknown'));

            $context = AuditContext::fromRuntime(
                $this->option('business') ?: null,
                $this->option('location') ?: null,
                null
            );

            $run = $engine->run(
                $context,
                $this->option('module') ?: [],
                $this->option('rule') ?: []
            );

            $this->info('Completed '.$run->run_no.' with '.data_get($run->summary, 'findings', 0).' finding(s).');
            return 0;
        } catch (\Throwable $e) {
            $this->error('Audit run failed safely: '.$e->getMessage());
            return 1;
        } finally {
            if ($initializedHere) {
                $tenants->end();
            }
        }
    }
}
