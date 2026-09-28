<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewPump extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_pumps';

    public function tank() { return $this->belongsTo(PdirectnewTank::class, 'tank_id'); }
    public function assignments() { return $this->hasMany(PdirectnewAssignment::class, 'pump_id'); }
}
