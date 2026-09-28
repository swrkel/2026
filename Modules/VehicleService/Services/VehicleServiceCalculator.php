<?php

namespace Modules\VehicleService\Services;

class VehicleServiceCalculator
{
    public function calculateLines(array $lines): array
    {
        $result = [
            'lines' => [],
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'total_amount' => 0,
        ];

        foreach ($lines as $index => $line) {
            $name = trim((string)($line['item_name'] ?? ''));
            $qty = $this->num($line['quantity'] ?? 0);
            $unit = $this->num($line['unit_price'] ?? 0);
            $discount = $this->num($line['discount_amount'] ?? 0);
            $tax = $this->num($line['tax_amount'] ?? 0);

            if ($name === '' || $qty <= 0) {
                continue;
            }

            $gross = $qty * $unit;
            $lineTotal = max(0, $gross - $discount + $tax);

            $result['subtotal'] += $gross;
            $result['discount_total'] += $discount;
            $result['tax_total'] += $tax;
            $result['total_amount'] += $lineTotal;
            $result['lines'][] = [
                'product_id' => !empty($line['product_id']) ? (int)$line['product_id'] : null,
                'variation_id' => !empty($line['variation_id']) ? (int)$line['variation_id'] : null,
                'item_type' => !empty($line['product_id']) ? 'product' : 'custom',
                'item_name' => $name,
                'description' => $line['description'] ?? null,
                'quantity' => $qty,
                'unit_price' => $unit,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'line_total' => $lineTotal,
                'sort_order' => $index,
            ];
        }

        return $result;
    }

    private function num($value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '', $value);
        }
        return round((float)$value, 4);
    }
}
