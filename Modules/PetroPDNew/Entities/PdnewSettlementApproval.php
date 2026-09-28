<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementApproval extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_approvals';
    protected $casts = [
        'acted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }
}
