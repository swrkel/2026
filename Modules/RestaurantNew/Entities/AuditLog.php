<?php
namespace Modules\RestaurantNew\Entities;

class AuditLog extends RestnewModel
{
    protected $table = 'restnew_audit_logs';
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];
}
