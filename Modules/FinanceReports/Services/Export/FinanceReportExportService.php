<?php

namespace Modules\FinanceReports\Services\Export;

use Illuminate\Support\Collection;

class FinanceReportExportService
{
    public function payload(string $title, array $filters, $rows, array $totals = []): array
    {
        return [
            'title' => $title,
            'filters' => $filters,
            'rows' => $rows instanceof Collection ? $rows->values()->all() : (array) $rows,
            'totals' => $totals,
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'read_only' => true,
        ];
    }

    public function supportedFormats(): array
    {
        return ['pdf', 'excel', 'csv', 'print'];
    }
}
