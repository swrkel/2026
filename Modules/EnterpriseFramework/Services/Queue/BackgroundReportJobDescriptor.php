<?php

namespace Modules\EnterpriseFramework\Services\Queue;

class BackgroundReportJobDescriptor
{
    public function describe(string $module, string $report, array $context = []): array
    {
        return [
            'module' => $module,
            'report' => $report,
            'context' => $context,
            'status' => 'queued',
            'queued_at' => now()->toDateTimeString(),
            'read_only' => true,
        ];
    }
}
