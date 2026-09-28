<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewAssignment extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_assignments';
    protected $casts = ['received_at' => 'datetime', 'closed_at' => 'datetime'];

    public function shift() { return $this->belongsTo(PdirectnewShift::class, 'shift_id'); }
    public function operator() { return $this->belongsTo(PdirectnewOperator::class, 'operator_id'); }
    public function pump() { return $this->belongsTo(PdirectnewPump::class, 'pump_id'); }
}
