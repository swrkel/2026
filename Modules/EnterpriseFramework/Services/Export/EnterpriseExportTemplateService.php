<?php

namespace Modules\EnterpriseFramework\Services\Export;

class EnterpriseExportTemplateService
{
    public function formats(): array
    {
        return ['pdf', 'excel', 'csv', 'print'];
    }

    public function template(string $format, array $context = []): array
    {
        return [
            'format' => $format,
            'title' => $context['title'] ?? 'Enterprise Report',
            'company' => $context['company'] ?? null,
            'branch' => $context['branch'] ?? 'Consolidated',
            'generated_by' => $context['generated_by'] ?? null,
            'generated_at' => now()->format(config('enterpriseframework.datetime_format', 'Y-m-d H:i:s')),
            'include_filters' => true,
            'include_footer' => true,
        ];
    }
}
