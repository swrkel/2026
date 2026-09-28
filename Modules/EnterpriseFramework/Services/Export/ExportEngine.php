<?php

namespace Modules\EnterpriseFramework\Services\Export;

class ExportEngine
{
    public function formats(): array
    {
        return config('enterpriseframework.default_export_formats', ['pdf', 'excel', 'csv', 'print']);
    }

    public function export(array $report, string $format): array
    {
        return ['status' => 'queued', 'format' => $format, 'report' => $report['definition']['name'] ?? 'report'];
    }
}
