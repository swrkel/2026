<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneOperatorSession extends PoneBaseModel
{
    protected $table = 'pone_operator_sessions';
    protected $casts = [
        'logged_in_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    public function operatorProfile() { return $this->belongsTo(PonePdOperator::class, 'operator_profile_id'); }
    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
}
