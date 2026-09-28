<?php

namespace Modules\SW\Entities;

class SettlementLine extends SWModel
{
    protected $table = 'sw_settlement_lines';

    protected $casts = [
        'opening_meter' => 'decimal:4',
        'closing_meter' => 'decimal:4',
        'testing_qty' => 'decimal:4',
        'quantity' => 'decimal:4',
        'rate' => 'decimal:4',
        'discount_value' => 'decimal:4',
        'amount_before_discount' => 'decimal:4',
        'amount' => 'decimal:4',
    ];

    public function settlement()
    {
        return $this->belongsTo(Settlement::class, 'settlement_id');
    }

    /**
     * 8043: Sold Qty is closing meter less starting meter.
     *
     * Testing quantity is shown as its own column and is NOT deducted here -
     * the mockup lists Sold Qty, Testing Qty and Total Qty separately, with
     * Sold Qty matching the plain meter difference.
     *
     * Kept on the model rather than in the controller so the list, the print
     * view and any future import all compute it the same way.
     */
    public function computeSoldQuantity(): float
    {
        $diff = (float) $this->closing_meter - (float) $this->opening_meter;

        return $diff > 0 ? round($diff, 4) : 0.0;
    }

    /**
     * Discount is applied to the line value, fixed or percentage.
     * A percentage over 100 or a fixed amount above the line total would make
     * the line negative, so both are clamped.
     */
    public function computeAmounts(): array
    {
        $sold = $this->computeSoldQuantity();
        $before = round($sold * (float) $this->rate, 4);

        $type = strtolower((string) $this->discount_type);
        $value = (float) $this->discount_value;
        $discount = 0.0;

        if ($type === 'percentage') {
            $discount = $before * (min(max($value, 0), 100) / 100);
        } elseif ($type === 'fixed') {
            $discount = min(max($value, 0), $before);
        }

        return [
            'quantity' => $sold,
            'amount_before_discount' => $before,
            'amount' => round($before - $discount, 4),
        ];
    }
}
