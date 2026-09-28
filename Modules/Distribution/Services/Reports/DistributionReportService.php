<?php

namespace Modules\Distribution\Services\Reports;

/**
 * Distribution-owned report service seam.
 *
 * Keeps report filter preparation and common totals inside the Distribution
 * module. Existing report queries can be migrated here safely in smaller steps.
 */
class DistributionReportService
{
    public function normalizeDateRange(?string $startDate, ?string $endDate): array
    {
        return [
            'start_date' => $startDate ?: date('Y-m-d'),
            'end_date' => $endDate ?: date('Y-m-d'),
        ];
    }

    public function sumAmount(iterable $rows, string $field = 'amount'): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            if (is_array($row)) {
                $total += (float) ($row[$field] ?? 0);
            } elseif (is_object($row)) {
                $total += (float) ($row->{$field} ?? 0);
            }
        }
        return $total;
    }
}
