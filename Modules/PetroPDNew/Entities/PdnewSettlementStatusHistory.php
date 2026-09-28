<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementStatusHistory extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_status_histories';
    protected $casts = [
        'changed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }
}
