<?php

namespace Modules\PumperDashboardNew\Entities;

class PonePdOperator extends PoneBaseModel
{
    protected $table = 'pone_pd_operators';
    protected $casts = [
        'login_enabled' => 'boolean',
        'settings' => 'array',
        'last_login_at' => 'datetime',
    ];

    public function shifts() { return $this->hasMany(PoneShift::class, 'operator_profile_id'); }
    public function sessions() { return $this->hasMany(PoneOperatorSession::class, 'operator_profile_id'); }
}
