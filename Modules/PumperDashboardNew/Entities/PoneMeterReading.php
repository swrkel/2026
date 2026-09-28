<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneMeterReading extends PoneBaseModel
{
    protected $table = 'pone_meter_readings';
    protected $casts = [
        'meter_value' => 'decimal:6',
        'testing_quantity' => 'decimal:6',
        'recorded_at' => 'datetime',
    ];

    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
    public function assignment() { return $this->belongsTo(PonePumpAssignment::class, 'assignment_id'); }
}
