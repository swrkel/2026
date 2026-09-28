<?php

namespace Modules\SW\Entities;

/**
 * Credit sales on a settlement.
 *
 * Auto-loaded from sw_daily_credit_sales for the selected shifts AND the
 * settlement's operator - both must match, so one operator's settlement never
 * picks up another's credit sales from the same shift.
 *
 * Copied rather than joined: the daily row records what was entered during the
 * shift, this records what was settled. When a figure is corrected at
 * settlement the two differ, and that difference is worth being able to see
 * rather than overwrite.
 */
class SettlementCreditSale extends SWModel
{
    protected $table = 'sw_settlement_credit_sales';

    protected $casts = [
        'order_date' => 'date',
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'unit_discount' => 'decimal:4',
        'amount_before_discount' => 'decimal:4',
        'amount' => 'decimal:4',
    ];

    /**
     * 8047: Amount before discount is qty x unit price.
     * Amount after discount subtracts the UNIT discount times the quantity -
     * not a flat amount, which is what the document specifies:
     *
     *     [Qty * Unit price] - [Qty * Unit Discount]
     *
     * Clamped so a discount larger than the price cannot make a line negative.
     */
    public function computeAmounts(): array
    {
        $qty = (float) $this->quantity;
        $unitPrice = (float) $this->unit_price;
        $before = round($qty * $unitPrice, 4);
        $discount = round(min(max((float) $this->unit_discount, 0), $unitPrice) * $qty, 4);

        return [
            'amount_before_discount' => $before,
            'credit_discount_amount' => $discount,
            'amount' => round($before - $discount, 4),
        ];
    }

    /** A row carried from the shift has no product detail; one added here does. */
    public function hasProductDetail(): bool
    {
        return ! empty($this->product_id);
    }

    public function settlement()
    {
        return $this->belongsTo(Settlement::class, 'settlement_id');
    }

    /** The shift entry this came from, or null if added at settlement. */
    public function dailyCreditSale()
    {
        return $this->belongsTo(DailyCreditSale::class, 'daily_credit_sale_id');
    }

    public function wasAddedAtSettlement(): bool
    {
        return empty($this->daily_credit_sale_id);
    }
}
