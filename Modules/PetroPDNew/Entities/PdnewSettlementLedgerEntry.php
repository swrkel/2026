<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementLedgerEntry extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_ledger_entries';
    protected $casts = [
        'entry_at' => 'datetime',
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }
}
