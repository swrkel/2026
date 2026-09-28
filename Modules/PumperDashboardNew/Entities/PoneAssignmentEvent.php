<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneAssignmentEvent extends PoneBaseModel
{
    protected $table = 'pone_assignment_events';
    protected $casts = [
        'meter_value' => 'decimal:6',
        'testing_quantity' => 'decimal:6',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function assignment() { return $this->belongsTo(PonePumpAssignment::class, 'assignment_id'); }
    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
}
