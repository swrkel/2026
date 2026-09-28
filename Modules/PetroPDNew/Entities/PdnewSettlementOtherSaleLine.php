<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementOtherSaleLine extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_other_sale_lines';
    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'amount' => 'decimal:4',
    ];

    public function otherSale()
    {
        return $this->belongsTo(PdnewSettlementOtherSale::class, 'other_sale_id');
    }
}
