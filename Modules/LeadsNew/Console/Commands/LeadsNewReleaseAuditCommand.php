<?php

namespace Modules\LeadsNew\Console\Commands;

use Illuminate\Console\Command;
use Modules\LeadsNew\Services\LeadsNewReleaseReadinessService;

class LeadsNewReleaseAuditCommand extends Command
{
    protected $signature = 'leads-new:release-audit {--json : Output JSON result}';
    protected $description = 'Run Leads-New standalone and release readiness checks.';

    public function handle(LeadsNewReleaseReadinessService $service): int
    {
        $result = $service->run();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info('Leads-New Release Audit');
        $this->line('Standalone audit: ' . data_get($result, 'standalone_audit.status'));
        $this->line('Route audit: ' . data_get($result, 'route_audit.status') . ' (' . data_get($result, 'route_audit.count') . ' routes)');

        foreach (data_get($result, 'file_checklist', []) as $name => $ok) {
            $this->line(($ok ? '[OK] ' : '[MISS] ') . $name);
        }

        return data_get($result, 'standalone_audit.status') === 'failed' ? self::FAILURE : self::SUCCESS;
    }
}
