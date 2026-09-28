<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneIntegrationLink extends PoneBaseModel
{
    protected $table = 'pone_integration_links';
    protected $casts = ['synced_at' => 'datetime'];
}
