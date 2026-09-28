<?php

namespace Modules\Customers\Exports;

class CustomerAgingExport extends CustomerCsvExport
{
    public function downloadAging(string $filename, array $aging)
    {
        $labels = [
            'current' => 'Current',
            'days_1_30' => '1 - 30 Days',
            'days_31_60' => '31 - 60 Days',
            'days_61_90' => '61 - 90 Days',
            'over_90' => 'Over 90 Days',
        ];

        $rows = [];
        foreach ($labels as $key => $label) {
            $rows[] = [$label, $this->moneyValue($aging[$key] ?? 0)];
        }
        $rows[] = ['Total', $this->moneyValue(array_sum($aging))];

        return $this->download($filename, ['Bucket', 'Amount'], $rows);
    }
}
