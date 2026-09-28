<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementCommission extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_commissions';
    protected $casts = [
        'commission_date' => 'date',
        'base_excess_amount' => 'decimal:4',
        'commission_rate' => 'decimal:4',
        'commission_amount' => 'decimal:4',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }
}
