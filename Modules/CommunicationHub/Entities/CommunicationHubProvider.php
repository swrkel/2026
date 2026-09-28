<?php

namespace Modules\CommunicationHub\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\CommunicationHub\Support\TenantConnection;

class CommunicationHubProvider extends Model
{
    protected $table = 'communication_hub_providers';

    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'payload' => 'array',
        'provider_config' => 'array',
        'placeholders' => 'array',
        'attempted_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
        'last_health_check_at' => 'datetime',
        'is_active' => 'boolean',
        'cost_per_message' => 'decimal:4',
    ];

    public function getConnectionName()
    {
        return TenantConnection::name() ?: parent::getConnectionName();
    }
}
