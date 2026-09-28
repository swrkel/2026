<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewAuditLog extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_audit_logs';
    protected $casts = [
        'metadata' => 'array',
        'details' => 'array',
        'filters' => 'array',
    ];
}
