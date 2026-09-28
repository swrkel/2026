<?php

namespace Modules\RestaurantNew\Services;

use Modules\RestaurantNew\Entities\MenuItem;

class OrderPricingService
{
    public function line(MenuItem $item, float $qty, float $modifierUnitTotal = 0, float $discount = 0, ?float $basePrice = null): array
    {
        $base = $basePrice ?? (float) $item->selling_price;
        $unit = $base + $modifierUnitTotal;
        $gross = $unit * $qty;
        $discount = max(0, min($gross, $discount));
        $taxable = $gross - $discount;
        $tax = round($taxable * ((float) $item->tax_rate / 100), 4);
        $service = round($taxable * ((float) $item->service_charge_rate / 100), 4);

        return [
            'unit_price' => $base,
            'modifier_total' => round($modifierUnitTotal * $qty, 4),
            'discount_amount' => round($discount, 4),
            'tax_amount' => $tax,
            'service_charge_amount' => $service,
            'line_total' => round($taxable + $tax + $service, 4),
            'subtotal' => round($gross, 4),
        ];
    }

    public function totals(array $lines): array
    {
        $subtotal = $discount = $tax = $service = $total = 0;
        foreach ($lines as $line) {
            $subtotal += (float) $line['subtotal'];
            $discount += (float) $line['discount_amount'];
            $tax += (float) $line['tax_amount'];
            $service += (float) $line['service_charge_amount'];
            $total += (float) $line['line_total'];
        }

        return [
            'subtotal' => round($subtotal, 4),
            'discount_total' => round($discount, 4),
            'tax_total' => round($tax, 4),
            'service_charge_total' => round($service, 4),
            'rounding_amount' => 0,
            'total_amount' => round($total, 4),
            'balance_amount' => round($total, 4),
        ];
    }
}
