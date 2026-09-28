<?php

namespace Modules\CommunicationHub\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\CommunicationHub\Support\TenantConnection;

class CommunicationHubApiRequestLog extends Model
{
    protected $table = 'communication_hub_api_request_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function getConnectionName()
    {
        return TenantConnection::name() ?: parent::getConnectionName();
    }
}
