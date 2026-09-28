<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementPump extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_pumps';
    protected $casts = [
        'opening_meter' => 'decimal:3',
        'closing_meter' => 'decimal:3',
        'testing_quantity' => 'decimal:3',
        'sold_quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'amount' => 'decimal:4',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }
}
