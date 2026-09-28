<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewMeterReading extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_meter_readings';
    protected $casts = ['recorded_at' => 'datetime'];

    public function shift() { return $this->belongsTo(PdirectnewShift::class, 'shift_id'); }
    public function operator() { return $this->belongsTo(PdirectnewOperator::class, 'operator_id'); }
    public function pump() { return $this->belongsTo(PdirectnewPump::class, 'pump_id'); }
    public function assignment() { return $this->belongsTo(PdirectnewAssignment::class, 'assignment_id'); }
}
