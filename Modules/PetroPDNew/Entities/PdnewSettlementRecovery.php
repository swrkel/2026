<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementRecovery extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_recoveries';
    protected $casts = [
        'recovery_date' => 'date',
        'amount' => 'decimal:4',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }
}
