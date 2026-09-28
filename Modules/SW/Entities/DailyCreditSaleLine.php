<?php

namespace Modules\SW\Entities;

/**
 * A product line on a daily credit sale.
 *
 * 8047: Amount (After Discount) = [Qty * Unit price] - [Qty * Unit Discount].
 * The discount is per UNIT, not a flat sum off the line - which is why
 * unit_discount is stored rather than a total.
 */
class DailyCreditSaleLine extends SWModel
{
    protected $table = 'sw_daily_credit_sale_lines';

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'unit_discount' => 'decimal:4',
        'amount_before_discount' => 'decimal:4',
        'amount' => 'decimal:4',
    ];

    public function sale()
    {
        return $this->belongsTo(DailyCreditSale::class, 'sw_daily_credit_sale_id');
    }

    /**
     * Kept on the model so the entry form, the settlement that carries these
     * forward, and any report all compute a line the same way.
     *
     * The discount is clamped to the unit price: a discount larger than the
     * price would make the line negative, which is a typo rather than an
     * intention.
     */
    public static function compute(float $qty, float $unitPrice, float $unitDiscount): array
    {
        $before = round($qty * $unitPrice, 4);
        $discount = round($qty * min(max($unitDiscount, 0), $unitPrice), 4);

        return [
            'amount_before_discount' => $before,
            'amount' => round($before - $discount, 4),
        ];
    }
}
