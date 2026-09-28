<?php

namespace Modules\CommunicationHub\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\CommunicationHub\Support\TenantConnection;

class CommunicationHubMarketplacePackage extends Model
{
    protected $table = 'communication_hub_marketplace_packages';

    protected $guarded = ['id'];

    protected $casts = [
        'capabilities' => 'array',
        'configuration_schema' => 'array',
        'sandbox_results' => 'array',
        'installed_at' => 'datetime',
        'last_tested_at' => 'datetime',
        'enabled_at' => 'datetime',
        'disabled_at' => 'datetime',
        'is_installed' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    public function getConnectionName()
    {
        return TenantConnection::name() ?: parent::getConnectionName();
    }
}
