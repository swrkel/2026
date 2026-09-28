<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneAuditLog extends PoneBaseModel
{
    public $timestamps = false;
    protected $table = 'pone_audit_logs';
    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
        'created_at' => 'datetime',
    ];
}
