<?php

namespace Modules\AirlineTicketingNew\Entities;

class AirlineTicketingAuditLog extends BaseAirlineTicketingModel
{
    public $timestamps = false;
    protected $table = 'atn_audit_logs';

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];
}
