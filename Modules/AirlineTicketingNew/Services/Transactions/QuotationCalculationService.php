<?php

namespace Modules\AirlineTicketingNew\Services\Transactions;

class QuotationCalculationService
{
    public function calculate(array $segments): array
    {
        $subtotal = 0.0;
        $tax = 0.0;
        $serviceFee = 0.0;
        $discount = 0.0;

        foreach ($segments as &$segment) {
            $base = (float) ($segment['base_fare'] ?? 0);
            $segmentTax = (float) ($segment['tax_amount'] ?? 0);
            $segmentFee = (float) ($segment['service_fee'] ?? 0);
            $segmentDiscount = (float) ($segment['discount_amount'] ?? 0);

            $segment['segment_total'] = round($base + $segmentTax + $segmentFee - $segmentDiscount, 4);

            $subtotal += $base;
            $tax += $segmentTax;
            $serviceFee += $segmentFee;
            $discount += $segmentDiscount;
        }

        return [
            'segments' => $segments,
            'subtotal' => round($subtotal, 4),
            'tax_total' => round($tax, 4),
            'service_fee_total' => round($serviceFee, 4),
            'discount_total' => round($discount, 4),
            'grand_total' => round($subtotal + $tax + $serviceFee - $discount, 4),
        ];
    }
}
