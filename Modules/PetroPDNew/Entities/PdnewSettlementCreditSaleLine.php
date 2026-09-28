<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementCreditSaleLine extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_credit_sale_lines';
    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'amount' => 'decimal:4',
    ];

    public function creditSale()
    {
        return $this->belongsTo(PdnewSettlementCreditSale::class, 'credit_sale_id');
    }
}
