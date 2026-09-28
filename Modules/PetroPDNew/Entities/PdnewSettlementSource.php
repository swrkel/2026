<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementSource extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_sources';
    protected $casts = [
        'source_closed_at' => 'datetime',
        'source_totals' => 'array',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }
}
