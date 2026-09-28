<?php

namespace Modules\Ran\Entities;

class AuditLog extends RanAuditModel
{
    protected $table = 'ran_audit_logs';
    protected $casts = ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];
}
