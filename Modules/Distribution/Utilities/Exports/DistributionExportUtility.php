<?php

namespace Modules\Distribution\Utilities\Exports;

use Modules\Distribution\Support\DistributionNumberFormatter;

class DistributionExportUtility
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

    public function normalizeRow(array $row, array $moneyColumns = [], array $quantityColumns = []): array
    {
        foreach ($moneyColumns as $column) {
            if (array_key_exists($column, $row)) {
                $row[$column] = $this->money($row[$column]);
            }
        }

        foreach ($quantityColumns as $column) {
            if (array_key_exists($column, $row)) {
                $row[$column] = $this->quantity($row[$column]);
            }
        }

        return $row;
    }
}
