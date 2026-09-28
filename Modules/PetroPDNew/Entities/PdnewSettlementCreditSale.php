<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementCreditSale extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_credit_sales';
    protected $casts = [
        'order_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:4',
        'confirmed' => 'boolean',
        'metadata' => 'array',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }

    public function lines()
    {
        return $this->hasMany(PdnewSettlementCreditSaleLine::class, 'credit_sale_id');
    }
}
