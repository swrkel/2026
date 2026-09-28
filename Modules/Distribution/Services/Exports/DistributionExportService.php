<?php

namespace Modules\Distribution\Services\Exports;

/**
 * Distribution-owned export service seam.
 *
 * Keeps export headings and row formatting inside the Distribution module so
 * exports can be maintained without main ERP export helpers.
 */
class DistributionExportService
{
    public function headings(array $headings): array
    {
        return array_values($headings);
    }

    public function rows(iterable $records, callable $mapper): array
    {
        $rows = [];
        foreach ($records as $record) {
            $rows[] = $mapper($record);
        }
        return $rows;
    }
}
