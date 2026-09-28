<?php

namespace Modules\CommunicationHub\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\CommunicationHub\Support\TenantConnection;

class CommunicationHubCampaign extends Model
{
    protected $table = 'communication_hub_campaigns';

    protected $guarded = [];

    public function getConnectionName()
    {
        return TenantConnection::name() ?: parent::getConnectionName();
    }

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'audience_filters' => 'array',
        'metadata' => 'array',
    ];
}
