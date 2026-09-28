<?php

namespace Modules\CommunicationHub\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\CommunicationHub\Support\TenantConnection;

class CommunicationHubMessage extends Model
{
    protected $table = 'communication_hub_messages';

    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'payload' => 'array',
        'provider_config' => 'array',
        'wallet_response' => 'array',
        'placeholders' => 'array',
        'attempted_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function getConnectionName()
    {
        return TenantConnection::name() ?: parent::getConnectionName();
    }
}
