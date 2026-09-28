<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementOtherSale extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_other_sales';
    protected $casts = [
        'sale_at' => 'datetime',
        'gross_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'net_amount' => 'decimal:4',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }

    public function lines()
    {
        return $this->hasMany(PdnewSettlementOtherSaleLine::class, 'other_sale_id');
    }
}
