<?php

namespace Modules\EnterpriseFramework\Services\Template;

class ReportTemplateService
{
    public function standardHeader(array $context = []): array
    {
        return [
            'company' => $context['company'] ?? config('app.name'),
            'branch' => $context['branch'] ?? 'Consolidated',
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
