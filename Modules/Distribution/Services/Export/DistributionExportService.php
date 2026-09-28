<?php

namespace Modules\Distribution\Services\Export;

class DistributionExportService
{
    public function datatableButtons(): array
    {
        return ['csv', 'excel', 'pdf', 'print', 'colvis'];
    }

    public function filename(string $prefix): string
    {
        return trim($prefix, '_- ') . '_' . now()->format('Ymd_His');
    }
}
