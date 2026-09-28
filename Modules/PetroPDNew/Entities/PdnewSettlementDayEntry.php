<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementDayEntry extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_day_entries';
    protected $casts = [
        'entry_at' => 'datetime',
        'quantity' => 'decimal:3',
        'amount' => 'decimal:4',
        'starting_meter' => 'decimal:3',
        'closing_meter' => 'decimal:3',
        'testing_quantity' => 'decimal:3',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }
}
