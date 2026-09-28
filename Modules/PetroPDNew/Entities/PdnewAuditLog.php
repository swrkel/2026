<?php

namespace Modules\PetroPDNew\Entities;

class PdnewAuditLog extends PdnewBaseModel
{
    protected $table = 'pdnew_audit_logs';
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];
}
