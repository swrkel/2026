<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementCollection extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_collections';
    protected $casts = [
        'collection_at' => 'datetime',
        'expected_amount' => 'decimal:4',
        'cash_amount' => 'decimal:4',
        'card_amount' => 'decimal:4',
        'cheque_amount' => 'decimal:4',
        'credit_amount' => 'decimal:4',
        'other_amount' => 'decimal:4',
        'declared_amount' => 'decimal:4',
        'difference_amount' => 'decimal:4',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }
}
