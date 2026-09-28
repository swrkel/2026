<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneShiftSettlementReference extends PoneBaseModel
{
    protected $table = 'pone_shift_settlement_references';
    protected $casts = ['settlement_date' => 'date'];
    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
}
