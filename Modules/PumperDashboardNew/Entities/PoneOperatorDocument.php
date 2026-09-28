<?php

namespace Modules\PumperDashboardNew\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class PoneOperatorDocument extends PoneBaseModel
{
    use SoftDeletes;
    protected $table = 'pone_operator_documents';
    protected $casts = ['file_size' => 'integer'];
    public function operatorProfile() { return $this->belongsTo(PonePdOperator::class, 'operator_profile_id'); }
    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
}
