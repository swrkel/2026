<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewShift extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_shifts';
    protected $casts = ['opened_at' => 'datetime', 'closed_at' => 'datetime'];
    public function assignments() { return $this->hasMany(PdirectnewAssignment::class, 'shift_id'); }
    public function operator() { return $this->belongsTo(PdirectnewOperator::class, 'operator_id'); }
}
