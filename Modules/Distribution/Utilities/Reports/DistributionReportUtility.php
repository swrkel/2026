<?php

namespace Modules\Distribution\Utilities\Reports;

use Modules\Distribution\Support\DistributionNumberFormatter;

class DistributionReportUtility
{
    public function money($value, ?int $precision = null): string
    {
        return DistributionNumberFormatter::money($value, $precision);
    }

    public function quantity($value, ?int $precision = null): string
    {
        return DistributionNumberFormatter::qty($value, $precision);
    }

    public function fuelQuantity($value): string
    {
        return DistributionNumberFormatter::fuelQty($value);
    }

    public function totals(array $rows, array $columns): array
    {
        $totals = array_fill_keys($columns, 0.0);

        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $totals[$column] += DistributionNumberFormatter::raw($row[$column] ?? 0);
            }
        }

        return $totals;
    }
}
