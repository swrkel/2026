<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneOperatorLedgerEntry extends PoneBaseModel
{
    protected $table = 'pone_operator_ledger_entries';
    protected $casts = ['entry_at' => 'datetime', 'debit' => 'decimal:4', 'credit' => 'decimal:4'];
    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
    public function operatorProfile() { return $this->belongsTo(PonePdOperator::class, 'operator_profile_id'); }
}
