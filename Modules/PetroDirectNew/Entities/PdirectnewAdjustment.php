<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewAdjustment extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_adjustments';
    protected $casts = ['approved_at' => 'datetime'];

    public function operator() { return $this->belongsTo(PdirectnewOperator::class, 'operator_id'); }
    public function settlement() { return $this->belongsTo(PdirectnewSettlement::class, 'settlement_id'); }
}
