<?php

namespace Modules\Audit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Audit\Services\TenantContextManager;

class AuditDiagnoseCommand extends Command
{
    protected $signature = 'audit:diagnose';
    protected $description = 'Diagnose Audit module loading, routes, tenancy and schema without changing data.';

    public function handle(TenantContextManager $tenants)
    {
        $provider = 'Modules\\Audit\\Providers\\AuditServiceProvider';
        $routeFile = module_path('Audit', 'Routes/web.php');
        $bootstrapFile = module_path('Audit', 'bootstrap.php');

        $rows = [
            ['Audit provider class', class_exists($provider) ? 'OK' : 'MISSING'],
            ['Audit provider loaded', !empty(app()->getLoadedProviders()[$provider]) ? 'YES' : 'NO'],
            ['Audit bootstrap file', is_file($bootstrapFile) ? 'OK' : 'MISSING'],
            ['Audit bootstrap loaded', defined('AUDIT_MODULE_BOOTSTRAP_LOADED') ? 'YES' : 'NO'],
            ['Audit route file', is_file($routeFile) ? 'OK' : 'MISSING'],
            ['audit.dashboard route', Route::has('audit.dashboard') ? 'YES' : 'NO'],
            ['audit:run command', array_key_exists('audit:run', $this->getApplication()->all()) ? 'YES' : 'NO'],
            ['Tenancy initialized', $tenants->initialized() ? 'YES' : 'NO'],
            ['Current tenant', $tenants->currentTenantKey() ?: 'none (normal for central CLI)'],
            ['Current database', $tenants->currentDatabase() ?: 'unknown'],
            ['audit_runs table', $this->tableExists('audit_runs') ? 'YES' : 'NO'],
            ['audit_findings table', $this->tableExists('audit_findings') ? 'YES' : 'NO'],
            ['Database connection', (string) config('database.default')],
            ['Route prefix', (string) config('audit.route_prefix', 'audit')],
        ];

        $this->table(['Check', 'Result'], $rows);

        if (!Route::has('audit.dashboard')) {
            $this->error('Audit web route is not registered.');
            return 1;
        }

        $this->info('Audit module bootstrap and web routes are registered.');
        if (!$tenants->initialized()) {
            $this->comment('CLI is currently in central context. Use audit:run --tenant=<tenant-id-or-domain> for a real tenant audit.');
        }
        return 0;
    }

    protected function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
