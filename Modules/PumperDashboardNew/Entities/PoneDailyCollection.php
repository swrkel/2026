<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneDailyCollection extends PoneBaseModel
{
    protected $table = 'pone_daily_collections';
    protected $casts = [
        'collection_at' => 'datetime',
        'expected_amount' => 'decimal:4',
        'cash_amount' => 'decimal:4',
        'card_amount' => 'decimal:4',
        'cheque_amount' => 'decimal:4',
        'credit_amount' => 'decimal:4',
        'other_amount' => 'decimal:4',
        'declared_amount' => 'decimal:4',
        'difference_amount' => 'decimal:4',
        'voided_at' => 'datetime',
    ];
    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
}
