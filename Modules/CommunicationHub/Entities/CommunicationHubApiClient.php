<?php

namespace Modules\CommunicationHub\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\CommunicationHub\Support\TenantConnection;

class CommunicationHubApiClient extends Model
{
    protected $table = 'communication_hub_api_clients';

    protected $guarded = ['id'];

    protected $casts = [
        'allowed_channels' => 'array',
        'allowed_ips' => 'array',
        'permissions' => 'array',
        'rate_limits' => 'array',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function getConnectionName()
    {
        return TenantConnection::name() ?: parent::getConnectionName();
    }
}
