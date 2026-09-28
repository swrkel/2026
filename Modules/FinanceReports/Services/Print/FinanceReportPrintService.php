<?php

namespace Modules\FinanceReports\Services\Print;

class FinanceReportPrintService
{
    public function header(string $title, array $context): array
    {
        return [
            'title' => $title,
            'company' => $context['company'] ?? '',
            'branch' => $context['branch'] ?? 'Consolidated',
            'period' => $context['period'] ?? '',
            'generated_by' => auth()->user()->first_name ?? auth()->user()->username ?? 'System',
            'generated_on' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
