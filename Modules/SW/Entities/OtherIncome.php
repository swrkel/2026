<?php

namespace Modules\SW\Entities;

/**
 * 8045: Other Income — services rather than goods.
 *
 * The Service dropdown lists products NOT under stock management, so nothing
 * here touches stock.
 *
 * The rate is stored per line rather than read from the product, because a
 * permitted user may override it. "The product costs this now" does not answer
 * what was charged then, and that is the question asked when a figure is
 * queried months later.
 */
class OtherIncome extends SWModel
{
    protected $table = 'sw_other_income';

    protected $casts = [
        'quantity' => 'decimal:4',
        'rate' => 'decimal:4',
        'amount' => 'decimal:4',
        'price_edited' => 'boolean',
    ];

    public function settlement()
    {
        return $this->belongsTo(Settlement::class, 'settlement_id');
    }

    public function computeAmount(): float
    {
        return round((float) $this->quantity * (float) $this->rate, 4);
    }
}
