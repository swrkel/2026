<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneExcessCommission extends PoneBaseModel
{
    protected $table = 'pone_excess_commissions';
    protected $casts = [
        'commission_date' => 'date',
        'base_excess_amount' => 'decimal:4',
        'commission_rate' => 'decimal:6',
        'commission_amount' => 'decimal:4',
        'voided_at' => 'datetime',
    ];
    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
    public function operatorProfile() { return $this->belongsTo(PonePdOperator::class, 'operator_profile_id'); }
}
