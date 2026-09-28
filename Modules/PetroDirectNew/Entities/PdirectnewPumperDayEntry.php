<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewPumperDayEntry extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_pumper_day_entries';
    protected $casts = ['entry_date' => 'date'];

    public function operator() { return $this->belongsTo(PdirectnewOperator::class, 'operator_id'); }
    public function shift() { return $this->belongsTo(PdirectnewShift::class, 'shift_id'); }
}
