<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneLoginAttempt extends PoneBaseModel
{
    protected $table = 'pone_login_attempts';
    protected $casts = ['blocked_until' => 'datetime', 'last_attempt_at' => 'datetime'];
}
