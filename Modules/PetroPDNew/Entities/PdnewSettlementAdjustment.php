<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementAdjustment extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_adjustments';
    protected $casts = [
        'requested_amount' => 'decimal:4',
        'approved_amount' => 'decimal:4',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }
}
