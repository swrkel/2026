<?php

namespace Modules\EnterpriseFramework\Services\Audit;

use Illuminate\Support\Facades\Log;

class EnterpriseAuditLogger
{
    public function log(string $action, array $context = []): void
    {
        Log::info('EnterpriseFrameworkAudit', [
            'action' => $action,
            'context' => $context,
            'logged_at' => now()->toDateTimeString(),
        ]);
    }

    public function reportViewed(string $module, string $report, array $filters = []): void
    {
        $this->log('report_viewed', compact('module', 'report', 'filters'));
    }

    public function reportExported(string $module, string $report, string $format, array $filters = []): void
    {
        $this->log('report_exported', compact('module', 'report', 'format', 'filters'));
    }
}
