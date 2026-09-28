<?php

namespace Modules\SW\Entities;

/**
 * 8044: Other Sales — stock items sold during the shift.
 *
 * Distinct from meter sales: these come out of a STORE and reduce stock, where
 * meter sales come off a pump. The discount treatment is the same.
 */
class OtherSale extends SWModel
{
    protected $table = 'sw_other_sales';

    protected $casts = [
        'balance_stock' => 'decimal:4',
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
     * Line value, before and after discount.
     *
     * The same rule as meter sales, and kept on the model for the same reason:
     * the entry form, the print view and any import must agree. A percentage
     * over 100 or a fixed amount above the line total would make the line
     * negative, so both are clamped.
     */
    public function computeAmounts(): array
    {
        $before = round((float) $this->quantity * (float) $this->rate, 4);

        $type = strtolower((string) $this->discount_type);
        $value = (float) $this->discount_value;
        $discount = 0.0;

        if ($type === 'percentage') {
            $discount = $before * (min(max($value, 0), 100) / 100);
        } elseif ($type === 'fixed') {
            $discount = min(max($value, 0), $before);
        }

        return [
            'amount_before_discount' => $before,
            'amount' => round($before - $discount, 4),
        ];
    }
}
