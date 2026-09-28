<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneShortageRecovery extends PoneBaseModel
{
    protected $table = 'pone_shortage_recoveries';
    protected $casts = ['recovery_date' => 'date', 'amount' => 'decimal:4', 'voided_at' => 'datetime'];
    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
    public function operatorProfile() { return $this->belongsTo(PonePdOperator::class, 'operator_profile_id'); }
}
