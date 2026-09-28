<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneDayEntry extends PoneBaseModel
{
    protected $table = 'pone_day_entries';
    protected $casts = [
        'quantity' => 'decimal:6',
        'amount' => 'decimal:4',
        'starting_meter' => 'decimal:6',
        'closing_meter' => 'decimal:6',
        'testing_quantity' => 'decimal:6',
        'entry_at' => 'datetime',
        'edited_at' => 'datetime',
        'voided_at' => 'datetime',
    ];
    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
    public function assignment() { return $this->belongsTo(PonePumpAssignment::class, 'assignment_id'); }
}
