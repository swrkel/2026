<?php

namespace Modules\PumperDashboardNew\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class PoneOperatorNote extends PoneBaseModel
{
    use SoftDeletes;
    protected $table = 'pone_operator_notes';
    public function operatorProfile() { return $this->belongsTo(PonePdOperator::class, 'operator_profile_id'); }
    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
}
