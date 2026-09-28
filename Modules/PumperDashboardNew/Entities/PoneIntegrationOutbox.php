<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneIntegrationOutbox extends PoneBaseModel
{
    protected $table = 'pone_integration_outbox';
    protected $casts = [
        'payload' => 'array',
        'available_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
