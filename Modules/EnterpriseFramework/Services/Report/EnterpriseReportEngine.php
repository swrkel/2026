<?php

namespace Modules\EnterpriseFramework\Services\Report;

class EnterpriseReportEngine
{
    public function build(array $definition, array $context = []): array
    {
        return [
            'definition' => $definition,
            'context' => $context,
            'generated_at' => now()->format(config('enterpriseframework.datetime_format', 'Y-m-d H:i:s')),
            'read_only' => true,
            'filters' => $context['filters'] ?? [],
            'rows' => $definition['rows'] ?? [],
            'totals' => $definition['totals'] ?? [],
        ];
    }
}
